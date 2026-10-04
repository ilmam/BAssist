---
title: Collaboration — Comments, Review & History
---

## Context & Definition

Requirements are only useful once the right people have **discussed** them, **agreed** to them, and can **see how they changed**. BABOK covers this across Task 4.4 *Communicate Business Analysis Information*, Task 5.5 *Approve Requirements* and Task 5.1 *Trace Requirements*.

BAssist supports it with three tools that sit at the bottom of every item's view (open an item from its code chip, e.g. **FR-3**, or the eye icon in a list):

- **Comments** — threaded discussion attached to the item, with @mentions.
- **Review** — a formal decision: *Approve* or *Request changes* (stakeholder needs, features, functional and non-functional requirements).
- **History** — an automatic log of who created, changed, approved or reset the item.

Comments and review are on the item's **view**, not on its edit form, so a discussion never gets mixed up with unsaved edits.

## Why It Matters

Questions settled in chat or email are lost the moment someone new joins the project, and "who agreed to this?" becomes guesswork. Keeping the discussion, the decision and the change record on the item itself gives you:

- **One place to ask** — the question stays next to the requirement it is about.
- **Evidence of sign-off** — who approved what, when, and with which note.
- **Protection against silent drift** — if approved content is edited, the approval is reset so it is reviewed again.
- **Review-ready documents** — open questions and sign-off status print straight into the BABOK packages.

## How to Use

1. **Discuss with comments**
   - Type in the **Comments** box and click **Post comment** (or press **Ctrl+Enter**).
   - Mention a colleague with **@FirstName** (or **@First.Last** when first names clash). They see it under **Mentioned you** on their home page.
   - **Reply** to keep a thread together. When the question is settled, click **Resolve**; a new reply re-opens it.
   - Only the author can delete their own comment.

2. **Review and approve**
   - Items that go through review show a **review bar**: *Not yet reviewed*, *Approved*, *Changes requested* or *Needs re-approval*.
   - Approvers see two buttons:
     - **Approve** — note optional. The item moves to **Agreed**.
     - **Request changes** — a reason is required. The item moves to **Need Revision** and the reason is posted as an open comment so the author can reply.
   - Items you can approve that have no current decision appear under **Waiting for your review** on the home page, oldest first.

3. **Understand re-approval**
   - Editing the **content** of an approved item (title, statement, description, acceptance criteria, trigger, body …) **resets the approval**: the item returns to **Draft** and shows *Needs re-approval*, with the fields that changed.
   - Changing only **status** or **priority** (including quick edits and bulk edits in lists) keeps the approval.
   - Change requests keep their own approval flow (*Approve & taint*) and do not use the review bar.

4. **Read the history**
   - Expand **History** at the bottom of an item to see every entry, newest first: who, when, and each field's old → new value.
   - History starts from the day this feature was installed; earlier changes are not recorded.

5. **Print for review and sign-off**
   - In the BABOK documents and the full export, open comments print as **Word-style balloons** in the right margin (numbered C1, C2 …) and are listed again in **Open issues** at the end.
   - Each reviewable item carries a **sign-off stamp** (✓ Approved / ✗ Changes requested / ○ Not yet approved), summarised in the **Sign-off** table at the end.
   - Use the **Open comments: shown / hidden** button in the print toolbar to produce a clean copy for final sign-off. Resolved comments are never printed.

6. **Who can do what** (Administration → Roles)
   - **View** an item → read and post comments on it.
   - **Approve** (its own column in the permission matrix) → approve or request changes. It is separate from **Update** on purpose, so the person who edits a requirement does not have to be the person who signs it off.
