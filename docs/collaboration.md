# Collaboration: comments, review & history

**When do I care?** When you add a new entity that should support comments, review or history, or change how approvals behave. The user-facing guide is `resources/help/collaboration.md` (BA Guide → Collaboration).

## Pieces

| Concern | Code | Table(s) |
|---------|------|----------|
| Comment threads, @mentions | `CommentService`, `CommentController`, `pages/partials/comments.blade.php`, `resources/js/comments.js` | `comments`, `comment_mentions` |
| Review decisions | `ApprovalService`, `ReviewController`, `pages/partials/review-bar.blade.php`, `resources/js/review.js` | `approvals` |
| History | `ActivityRecorder`, `Observers/TrackedEntityObserver` | `activity_log` |
| Printing (BABOK docs, export pack) | `Support/PrintComments` + `pages/projects/partials/print-*.blade.php` | — |
| Permission | `EntityAccess::APPROVE`, `role_entity_permissions.can_approve` | — |

All three tables carry `project_id` and use `BelongsToTenant`, so every query is tenant-scoped like the entities themselves. Panels render inside `<x-details-view>`, so every details page and view modal gets them without per-entity Blade.

## Which entities get what

- **Comments + history:** `CommentService::COMMENTABLE` (entities with a `project_id`). `AppServiceProvider` registers `TrackedEntityObserver` on each.
- **Review:** `ApprovalService::APPROVABLE` — StakeholderNeed, Feature, FunctionalRequirement, NonFunctionalRequirement. Change requests are excluded on purpose; they keep the approve-and-taint flow.

To add an entity: put its model name in the list(s). It must have `project_id`; for review it must also have `status_id`.

## Rules worth knowing

- **Approve** → `status_id` = Agreed. **Request changes** (reason required) → Need Revision + an open comment with the reason.
- A new decision supersedes the previous one (`invalidated_reason = superseded`).
- **Re-approval:** on `updated`, if any column outside `ApprovalService::NON_CONTENT` changed and the current decision is *approved*, the approval is invalidated, status goes back to Draft, and an `approval_reset` history entry lists the fields.
  Status/priority changes (quick edits, bulk edits, CR taint) do not reset approvals.
- Setting the status from inside `ApprovalService` saves only `status_id`, which is in `NON_CONTENT`, so it never loops.
- History stores old → new per changed column (long text truncated to 240 chars); `updated_at`, `number` and similar bookkeeping columns are ignored. Wrap bulk maintenance in `app(ActivityRecorder::class)->pause(fn () => …)` to skip logging.
- Printing: controllers register a `PrintComments` instance per request (open threads, current approvals, and the comments on/off flag from `?comments=0`). Item partials ask it per article; the appendix lists only what the document actually printed.

## Tests

`tests/Feature/CommentsTest.php`, `tests/Feature/ReviewAndHistoryTest.php`.
