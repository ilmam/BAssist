<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Services\CommentService;
use App\Support\CrudEntityRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Comment threads (#8). Every action returns the re-rendered thread panel so the
 * page and the modal stay in sync without a reload.
 */
class CommentController extends Controller
{
    public function __construct(protected CommentService $comments) {}

    public function index(string $model, int $id): Response
    {
        $record = $this->comments->record(Str::studly($model), $id);
        $this->comments->markMentionsSeen($record, auth()->user());

        return $this->panel(Str::studly($model), $record);
    }

    public function store(Request $request, string $model, int $id): Response
    {
        $model = Str::studly($model);
        $record = $this->comments->record($model, $id);
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:'.CommentService::MAX_LENGTH],
            'parent_id' => ['nullable', 'integer'],
        ]);

        $this->comments->add($record, $validated['body'], isset($validated['parent_id']) ? (int) $validated['parent_id'] : null);

        return $this->panel($model, $record);
    }

    public function resolve(Request $request, Comment $comment): Response
    {
        [$model, $record] = $this->owner($comment);
        $this->comments->setResolved($comment, $request->boolean('resolved', true));

        return $this->panel($model, $record);
    }

    public function destroy(Comment $comment): Response
    {
        [$model, $record] = $this->owner($comment);
        $this->comments->delete($comment);

        return $this->panel($model, $record);
    }

    /**
     * @return array{0: string, 1: Model}
     */
    protected function owner(Comment $comment): array
    {
        $model = class_basename($comment->commentable_type);
        abort_unless(array_key_exists($model, CrudEntityRegistry::all()), 404);

        return [$model, $this->comments->record($model, (int) $comment->commentable_id)];
    }

    protected function panel(string $model, Model $record): Response
    {
        return response()->view('pages.partials.comments', [
            'commentModel' => $model,
            'commentRecordId' => (int) $record->getKey(),
            'threads' => $this->comments->threads($record),
            'mentionUsers' => $this->comments->tenantUsers(),
        ]);
    }
}
