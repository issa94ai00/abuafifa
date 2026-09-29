<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ContactMessageController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', ContactMessage::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'unread_only' => ['nullable', 'boolean'],
        ]);

        $messages = ContactMessage::query()
            ->when($request->boolean('unread_only'), fn ($q) => $q->unread())
            ->when($filters['search'] ?? null, function ($q, $search) {
                // Bound parameter — never interpolated into the query.
                $q->where(fn ($w) => $w
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%"));
            })
            ->latest()
            ->paginate($request->integer('per_page', 15))
            ->withQueryString()
            ->through(fn (ContactMessage $m) => [
                'id' => $m->id,
                'name' => $m->name,
                'phone' => $m->phone,
                'email' => $m->email,
                'subject' => $m->subject,
                // The message body is user-submitted text. It is passed
                // as data to Vue, which escapes it on render — it is
                // never interpolated into HTML anywhere.
                'message' => $m->message,
                'is_read' => $m->is_read,
                'created_at' => $m->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('Admin/Contacts/Index', [
            'messages' => $messages,
            'stats' => [
                'total' => ContactMessage::query()->count(),
                'unread' => ContactMessage::query()->unread()->count(),
            ],
            'filters' => [
                'search' => $filters['search'] ?? null,
                'unread_only' => $request->boolean('unread_only') ? 1 : 0,
            ],
        ]);
    }

    public function show(ContactMessage $message)
    {
        Gate::authorize('view', $message);

        if (! $message->is_read) {
            $message->update(['is_read' => true]);
        }

        return Inertia::render('Admin/Contacts/Show', [
            'message' => $message->only([
                'id', 'name', 'phone', 'email', 'subject', 'message', 'is_read', 'ip_address', 'created_at',
            ]),
        ]);
    }

    public function toggleRead(ContactMessage $message)
    {
        Gate::authorize('update', $message);

        $message->update(['is_read' => ! $message->is_read]);

        return back();
    }

    public function destroy(ContactMessage $message)
    {
        Gate::authorize('delete', $message);

        // The LogsActivity trait on the model writes the audit row.
        $message->delete();

        return back()->with('success', 'تم حذف الرسالة.');
    }
}
