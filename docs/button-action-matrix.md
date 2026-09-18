# PCMS Button and Action Matrix

| Area | Action | Actor | Validation/authorization | Success | Failure |
|---|---|---|---|---|---|
| Authentication | Sign up | Guest | Unique email, name, confirmed 10+ character password | Participant created | Safe field errors |
| Authentication | Login/remember | Guest | Active account, password, throttling | Session and optional secure token | Generic error |
| Authentication | Forgot/reset | Guest | Random, hashed, unexpired, unused token | Password changed | Generic/expired error |
| Authentication | Logout | Authenticated | CSRF | Session and cookie destroyed | No state-changing GET |
| Contests | Filter/view | Anyone | Approved public results | Database-driven page | Empty/404 state |
| Registration | Register/cancel | Participant | Approval, deadline, uniqueness, role | Status updated | Business-rule message |
| Problems | View/submit | Participant | Submission requires registration and ongoing contest | Editor/queued submission | 403/eligibility error |
| Contest CRUD | Create/edit/submit/cancel | Organizer | Ownership and schedule checks | Draft/review/lifecycle update | Validation/ownership error |
| Problem CRUD | Create/edit/archive | Organizer | Contest ownership and limits | Persisted state | Validation/ownership error |
| Test cases | Create/edit/delete | Organizer | Problem ownership and valid values | Persisted test | Validation/ownership error |
| Submissions | Record verdict | Organizer | Ownership and verdict allow-list | Judged state | Invalid/unauthorized |
| Announcements | Create/edit/archive | Organizer | Contest ownership and required copy | Persisted state | Validation/ownership error |
| Clarifications | Ask | Participant | Registration and length | Open question | Validation/eligibility error |
| Clarifications | Answer | Organizer | Contest ownership and answer | Answered state | Validation/ownership error |
| Leaderboard | Select contest | Anyone | Approved contest | Calculated standings | Empty state |
| Profile | Update/password | Authenticated | Own account/current password | Account updated | Safe error |
| Users | Status/role | Admin | Enum allow-list and CSRF | Access updated | Invalid/unauthorized |
| Approval | Approve/reject | Admin | Pending; rejection reason | Decision and audit | Validation error |
| Monitoring | Contests/submissions/audit | Admin | Admin role | Read-only system view | 403 |

