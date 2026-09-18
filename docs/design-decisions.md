# PCMS Design Decisions

## Canonical identity

The product name is PCMS. CodeElite references were removed. The canonical fixture is PCMS CodeSprint 2026, with matching problems, registrations, submissions, announcements, and clarifications.

## Navigation

The inconsistent Figma sidebar/top-bar patterns became one authenticated desktop sidebar with a mobile drawer. Public pages use a compact top bar. Labels are canonical: Contests, Problems, My Submissions, Clarifications, Leaderboard, Announcements, and Profile & Settings.

## Figma extensions

The purple/lavender palette, rounded cards, status chips, summary cards, tables, and spacious forms are retained. Missing participant, organizer, and administrator screens use the same tokens. Status text accompanies color.

## Corrected UX logic

- Participant counts come from registrations.
- Contest lifecycle comes from approval, cancellation, and schedule.
- One canonical submission verdict appears everywhere.
- Problem pages include sample output, limits, and constraints.
- Buttons map to real routes/forms.
- Empty, error, permission, focus, responsive, and confirmation states exist.
- Footer year is dynamic.

## Security boundary

Submissions support safe authorized review without PHP executing untrusted code. Automatic judging remains deferred until a separately isolated judge service is configured.

## Remaining visual verification

No editable Figma source was supplied, so measurements were derived from the PDF export. Pixel-level browser comparison remains pending until OCI8 setup allows all database-driven pages to render over HTTP.

