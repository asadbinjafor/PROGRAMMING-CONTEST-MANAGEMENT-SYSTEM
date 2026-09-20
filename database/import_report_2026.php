<?php
declare(strict_types=1);

/**
 * Import the archived ADBMS report fixtures into the live PCMS schema.
 *
 * Usage: php database/import_report_2026.php --dry-run
 *        php database/import_report_2026.php --apply
 *        php database/import_report_2026.php --verify
 *
 * This deliberately does not recreate the incompatible report tables, copy
 * plaintext report passwords, update existing users, or store leaderboard rows.
 */

require dirname(__DIR__) . '/app/bootstrap.php';

use PCMS\Support\Database;

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

$mode = $argv[1] ?? '--dry-run';
if (!in_array($mode, ['--dry-run', '--apply', '--verify'], true) || count($argv) !== 2) {
    fwrite(STDERR, "Usage: php database/import_report_2026.php --dry-run|--apply|--verify\n");
    exit(2);
}

function one(string $sql, array $bind = []): ?array
{
    return Database::one($sql, $bind);
}

function countRows(string $sql, array $bind = []): int
{
    return (int) Database::scalar($sql, $bind);
}

function insert(string $sql, array $bind): void
{
    if (Database::execute($sql, $bind, false) !== 1) {
        throw new RuntimeException('An import insert did not affect exactly one row.');
    }
}

function nextId(string $sequence): int
{
    $allowed = [
        'seq_app_user', 'seq_contest', 'seq_registration', 'seq_problem',
        'seq_test_case', 'seq_submission', 'seq_announcement', 'seq_clarification',
    ];
    if (!in_array($sequence, $allowed, true)) {
        throw new LogicException('Unexpected sequence.');
    }
    return (int) Database::scalar("SELECT {$sequence}.NEXTVAL FROM dual");
}

function oracleTimestamp(string $local): string
{
    return $local . ' Asia/Dhaka';
}

function reportContests(): array
{
    return [
        101 => [
            'title' => 'AIUB Programming Contest 2026',
            'slug' => 'report-2026-aiub-programming-contest',
            'description' => 'University programming contest',
            'start' => '2026-07-20 10:00:00', 'end' => '2026-07-20 13:00:00',
            'visibility' => 'PUBLIC', 'owner_report_id' => 2,
        ],
        102 => [
            'title' => 'BRAC Programming Contest 2026',
            'slug' => 'report-2026-brac-programming-contest',
            'description' => 'Online algorithm contest',
            'start' => '2026-07-22 14:00:00', 'end' => '2026-07-22 17:00:00',
            'visibility' => 'PUBLIC', 'owner_report_id' => 2,
        ],
        103 => [
            'title' => 'NSU Programming Contest 2026',
            'slug' => 'report-2026-nsu-programming-contest',
            'description' => 'Programming contest in technology',
            'start' => '2026-07-30 10:00:00', 'end' => '2026-07-30 14:00:00',
            'visibility' => 'PUBLIC', 'owner_report_id' => 4,
        ],
        104 => [
            'title' => 'Dhaka Inter-University Coding Challenge',
            'slug' => 'report-2026-dhaka-inter-university-coding-challenge',
            'description' => 'Coding contest for university students',
            'start' => '2026-08-05 09:00:00', 'end' => '2026-08-05 14:00:00',
            'visibility' => 'PRIVATE', 'owner_report_id' => 4,
        ],
        105 => [
            'title' => 'Independence Day Online Contest',
            'slug' => 'report-2026-independence-day-online-contest',
            'description' => 'National online programming contest',
            'start' => '2026-03-26 20:00:00', 'end' => '2026-03-26 23:00:00',
            'visibility' => 'PUBLIC', 'owner_report_id' => 2,
        ],
    ];
}

function reportProblems(): array
{
    return [
        201 => [101, 'Sum of Two Numbers', 'Read two integers and print their sum.', 'EASY', 1000, 64,
            'Two space-separated integers.', 'Print their sum.', '12 30', '42'],
        202 => [102, 'Dynamic Sequence Queries', 'Process update and range sum queries efficiently.', 'HARD', 3000, 256,
            'The archived report does not provide a complete input specification; the recorded test fixture is retained for reference.',
            'Print the required query results. The archived report does not define the query syntax.', null, null],
        203 => [103, 'Balanced Brackets', 'Determine whether the bracket sequence is balanced.', 'EASY', 1000, 64,
            'A bracket sequence.', 'Print YES if balanced; otherwise print NO.', '[({})]', 'YES'],
        204 => [104, 'Shortest Path Delivery', 'Find the minimum delivery cost between two cities.', 'MEDIUM', 2000, 128,
            'The archived report does not provide a complete graph input specification; the recorded test fixture is retained for reference.',
            'Print the minimum delivery cost.', null, null],
        205 => [105, 'River Crossing Optimization', 'Find the minimum time required to cross the river.', 'HARD', 2500, 128,
            'The first value is the number of people, followed by their crossing times.',
            'Print the minimum total crossing time.', '4 1 2 5 10', '17'],
    ];
}

function currentUser(string $email, string $role): int
{
    $row = one(
        "SELECT u.user_id,u.account_status FROM app_user u JOIN user_role ur ON ur.user_id=u.user_id JOIN role r ON r.role_id=ur.role_id WHERE LOWER(u.email)=LOWER(:email) AND r.role_code=:role",
        ['email' => $email, 'role' => $role]
    );
    if (!$row || $row['account_status'] !== 'ACTIVE') {
        throw new RuntimeException("Required existing {$role} account is missing or inactive: {$email}");
    }
    return (int) $row['user_id'];
}

function expectedSlugs(): array
{
    return array_column(reportContests(), 'slug');
}

function existingImport(): array
{
    $found = [];
    foreach (expectedSlugs() as $slug) {
        $row = one('SELECT contest_id,contest_title FROM contest WHERE slug=:slug', ['slug' => $slug]);
        if ($row) $found[$slug] = $row;
    }
    return $found;
}

function verifyImport(): array
{
    $contests = reportContests();
    $found = existingImport();
    if (count($found) !== count($contests)) {
        throw new RuntimeException('Expected five imported contests; found ' . count($found) . '.');
    }
    $map = [];
    foreach ($contests as $reportId => $contest) {
        $row = $found[$contest['slug']] ?? null;
        if (!$row || $row['contest_title'] !== $contest['title']) {
            throw new RuntimeException("Contest {$reportId} title/slug mismatch.");
        }
        $map[$reportId] = (int) $row['contest_id'];
        $id = $map[$reportId];
        foreach ([
            'problem' => 'problem', 'test_case' => 'test case',
            'submission' => 'submission', 'announcement' => 'announcement',
            'clarification' => 'clarification', 'contest_registration' => 'registration',
        ] as $table => $label) {
            $sql = match ($table) {
                'problem', 'announcement', 'clarification', 'contest_registration' =>
                    "SELECT COUNT(*) FROM {$table} WHERE contest_id=:id",
                'test_case', 'submission' =>
                    "SELECT COUNT(*) FROM {$table} x JOIN problem p ON p.problem_id=x.problem_id WHERE p.contest_id=:id",
            };
            if (countRows($sql, ['id' => $id]) !== 1) {
                throw new RuntimeException("Contest {$reportId} must have one imported {$label}.");
            }
        }
        if (countRows('SELECT COUNT(*) FROM v_leaderboard WHERE contest_id=:id', ['id' => $id]) !== 1) {
            throw new RuntimeException("Contest {$reportId} leaderboard row is missing.");
        }
        $status = one('SELECT lifecycle_status FROM v_contest_status WHERE contest_id=:id', ['id' => $id]);
        if (($status['lifecycle_status'] ?? '') !== 'COMPLETED') {
            throw new RuntimeException("Contest {$reportId} should be completed by its historical date.");
        }
    }
    foreach (['roman@gmail.com' => 'ADMIN', 'arman@gmail.com' => 'ORGANIZER'] as $email => $role) {
        $user = one(
            'SELECT u.account_status,r.role_code FROM app_user u JOIN user_role ur ON ur.user_id=u.user_id JOIN role r ON r.role_id=ur.role_id WHERE LOWER(u.email)=LOWER(:email)',
            ['email' => $email]
        );
        if (!$user || $user['account_status'] !== 'DISABLED' || $user['role_code'] !== $role) {
            throw new RuntimeException("Imported {$email} account/role is missing or unexpectedly active.");
        }
    }
    return $map;
}

function doImport(): array
{
    $existing = existingImport();
    if ($existing) {
        if (count($existing) === 5) return verifyImport();
        throw new RuntimeException('Partial prior report import detected. No data changed.');
    }
    foreach (['roman@gmail.com', 'arman@gmail.com'] as $email) {
        if (countRows('SELECT COUNT(*) FROM app_user WHERE LOWER(email)=LOWER(:email)', ['email' => $email])) {
            throw new RuntimeException("Email already exists; refusing to overwrite: {$email}");
        }
    }

    $users = [
        2 => currentUser('organizer@pcms.test', 'ORGANIZER'),
        3 => currentUser('participant@pcms.test', 'PARTICIPANT'),
        5 => currentUser('shishir@pcms.test', 'PARTICIPANT'),
    ];
    $newUsers = [
        1 => ['Roman Sheikh', 'roman@gmail.com', 'ADMIN', 1850, 'roman.jpg', '2026-06-01 00:00:00'],
        4 => ['MD Arman', 'arman@gmail.com', 'ORGANIZER', 1940, 'arman.jpg', '2026-06-04 00:00:00'],
    ];
    foreach ($newUsers as $reportId => [$name, $email, $role, $rating, $image, $created]) {
        $id = nextId('seq_app_user');
        // Report passwords are plaintext and are deliberately discarded.
        $unusableSecret = bin2hex(random_bytes(32));
        insert(
            "INSERT INTO app_user(user_id,user_name,email,password_hash,institution,rating,profile_image,account_status,created_at) VALUES(:id,:name,:email,:hash,'AIUB',:rating,:image,'DISABLED',TO_TIMESTAMP_TZ(:created,'YYYY-MM-DD HH24:MI:SS TZR'))",
            ['id' => $id, 'name' => $name, 'email' => $email,
             'hash' => password_hash($unusableSecret, PASSWORD_DEFAULT), 'rating' => $rating,
             'image' => $image, 'created' => oracleTimestamp($created)]
        );
        insert(
            'INSERT INTO user_role(user_id,role_id) SELECT :id,role_id FROM role WHERE role_code=:role',
            ['id' => $id, 'role' => $role]
        );
        $users[$reportId] = $id;
    }

    $contestIds = [];
    foreach (reportContests() as $reportId => $contest) {
        $id = nextId('seq_contest');
        $deadline = (new DateTimeImmutable($contest['start'], new DateTimeZone('Asia/Dhaka')))
            ->modify('-1 day')->format('Y-m-d H:i:s');
        insert(
            "INSERT INTO contest(contest_id,organizer_user_id,contest_title,slug,description,start_time,end_time,registration_deadline,visibility,approval_status,contest_status) VALUES(:id,:owner,:title,:slug,:description,TO_TIMESTAMP_TZ(:start_time,'YYYY-MM-DD HH24:MI:SS TZR'),TO_TIMESTAMP_TZ(:end_time,'YYYY-MM-DD HH24:MI:SS TZR'),TO_TIMESTAMP_TZ(:deadline,'YYYY-MM-DD HH24:MI:SS TZR'),:visibility,'APPROVED','ACTIVE')",
            ['id' => $id, 'owner' => $users[$contest['owner_report_id']], 'title' => $contest['title'],
             'slug' => $contest['slug'], 'description' => $contest['description'],
             'start_time' => oracleTimestamp($contest['start']), 'end_time' => oracleTimestamp($contest['end']),
             'deadline' => oracleTimestamp($deadline), 'visibility' => $contest['visibility']]
        );
        $contestIds[$reportId] = $id;
    }

    $registrationUsers = [101 => 3, 102 => 3, 103 => 5, 104 => 5, 105 => 3];
    foreach ($registrationUsers as $reportContest => $reportUser) {
        $start = reportContests()[$reportContest]['start'];
        $registered = (new DateTimeImmutable($start, new DateTimeZone('Asia/Dhaka')))
            ->modify('-2 days')->format('Y-m-d H:i:s');
        insert(
            "INSERT INTO contest_registration(registration_id,contest_id,user_id,registration_status,registered_at) VALUES(:id,:contest,:user_id,'REGISTERED',TO_TIMESTAMP_TZ(:registered,'YYYY-MM-DD HH24:MI:SS TZR'))",
            ['id' => nextId('seq_registration'), 'contest' => $contestIds[$reportContest],
             'user_id' => $users[$reportUser], 'registered' => oracleTimestamp($registered)]
        );
    }

    $problemIds = [];
    foreach (reportProblems() as $reportId => [$reportContest, $title, $statement, $difficulty, $time, $memory, $input, $output, $sampleIn, $sampleOut]) {
        $id = nextId('seq_problem');
        insert(
            "INSERT INTO problem(problem_id,contest_id,problem_code,problem_title,problem_statement,input_format,output_format,sample_input,sample_output,difficulty,time_limit_ms,memory_limit_mb,display_order) VALUES(:id,:contest,'A',:title,:statement,:input_format,:output_format,:sample_input,:sample_output,:difficulty,:time_limit,:memory_limit,1)",
            ['id' => $id, 'contest' => $contestIds[$reportContest], 'title' => $title,
             'statement' => $statement, 'input_format' => $input, 'output_format' => $output,
             'sample_input' => $sampleIn, 'sample_output' => $sampleOut,
             'difficulty' => $difficulty, 'time_limit' => $time, 'memory_limit' => $memory]
        );
        $problemIds[$reportId] = $id;
    }

    $tests = [
        [201, '12 30', '42', 'N'],
        [202, '5 3 1 2 3 4 5', '9 22', 'Y'],
        [203, '[({})]', 'YES', 'N'],
        [204, '4 5 1 2 4 2 3 2 3 4 1', '7', 'Y'],
        [205, '4 1 2 5 10', '17', 'Y'],
    ];
    foreach ($tests as [$problem, $input, $output, $hidden]) {
        insert(
            'INSERT INTO test_case(test_case_id,problem_id,test_input,expected_output,is_hidden) VALUES(:id,:problem,:input,:output,:hidden)',
            ['id' => nextId('seq_test_case'), 'problem' => $problemIds[$problem],
             'input' => $input, 'output' => $output, 'hidden' => $hidden]
        );
    }

    $submissions = [
        [3, 201, 'CPP', 'Program to calculate the sum', 'ACCEPTED', 12.500, 1024, '2026-07-20 10:35:00'],
        [3, 202, 'JAVA', 'Program to process sequence queries', 'TIME_LIMIT_EXCEEDED', 3000.000, 32768, '2026-07-22 14:40:00'],
        [5, 203, 'PYTHON', 'Program to check balanced brackets', 'ACCEPTED', 48.750, 8192, '2026-07-30 10:25:00'],
        [5, 204, 'CPP', 'Program to find the shortest path', 'WRONG_ANSWER', 35.000, 2048, '2026-08-05 10:10:00'],
        [3, 205, 'C', 'Program to solve river crossing', 'PENDING', 0.000, 0, '2026-03-26 21:05:00'],
    ];
    foreach ($submissions as [$reportUser, $reportProblem, $language, $source, $verdict, $time, $memory, $submitted]) {
        insert(
            "INSERT INTO submission(submission_id,user_id,problem_id,language,source_code,verdict,execution_time_ms,memory_used_kb,judge_message,submitted_at,judged_at) VALUES(:id,:user_id,:problem,:language,:source,:verdict,:execution_time,:memory_used,:message,TO_TIMESTAMP_TZ(:submitted,'YYYY-MM-DD HH24:MI:SS TZR'),CASE WHEN :is_pending='Y' THEN NULL ELSE TO_TIMESTAMP_TZ(:judged,'YYYY-MM-DD HH24:MI:SS TZR') END)",
            ['id' => nextId('seq_submission'), 'user_id' => $users[$reportUser],
             'problem' => $problemIds[$reportProblem], 'language' => $language,
             'source' => $source, 'verdict' => $verdict, 'execution_time' => $time,
             'memory_used' => $memory,
             'message' => 'Imported academic report fixture; source_code is descriptive text, not executable code.',
             'submitted' => oracleTimestamp($submitted), 'is_pending' => $verdict === 'PENDING' ? 'Y' : 'N',
             'judged' => oracleTimestamp($submitted)]
        );
    }

    $announcements = [
        [101, 'Contest Registration Opened', 'Registration is now open for all participants.', '2026-07-01 00:00:00'],
        [102, 'Rule Update', 'Penalty will be calculated for accepted problems.', '2026-07-22 00:00:00'],
        [103, 'Contest Starting Soon', 'Participants should join thirty minutes early.', '2026-07-30 00:00:00'],
        [104, 'Team List Published', 'The final list of participating teams has been published.', '2026-08-01 00:00:00'],
        [105, 'Final Result Published', 'The final contest result has been published.', '2026-03-27 00:00:00'],
    ];
    foreach ($announcements as [$reportContest, $title, $message, $created]) {
        $ownerReportId = reportContests()[$reportContest]['owner_report_id'];
        insert(
            "INSERT INTO announcement(announcement_id,contest_id,author_user_id,announcement_title,message,created_at) VALUES(:id,:contest,:author,:title,:message,TO_TIMESTAMP_TZ(:created,'YYYY-MM-DD HH24:MI:SS TZR'))",
            ['id' => nextId('seq_announcement'), 'contest' => $contestIds[$reportContest],
             'author' => $users[$ownerReportId], 'title' => $title, 'message' => $message,
             'created' => oracleTimestamp($created)]
        );
    }

    $clarifications = [
        [101, 3, 'Can 64-bit integers be used?', 'Yes, 64-bit integers can be used.', 'ANSWERED'],
        [102, 3, 'Are query ranges one-indexed?', null, 'OPEN'],
        [103, 5, 'Does an empty string count as balanced?', 'An empty string will not occur.', 'CLOSED'],
        [104, 5, 'Are parallel edges allowed in the graph?', 'Yes, parallel edges are allowed.', 'ANSWERED'],
        [105, 3, 'Can two people cross together?', 'At most two people can cross together.', 'ANSWERED'],
    ];
    foreach ($clarifications as [$reportContest, $reportAsker, $question, $answer, $status]) {
        $contest = reportContests()[$reportContest];
        $asked = (new DateTimeImmutable($contest['start'], new DateTimeZone('Asia/Dhaka')))
            ->modify('+10 minutes')->format('Y-m-d H:i:s');
        $answered = (new DateTimeImmutable($asked, new DateTimeZone('Asia/Dhaka')))
            ->modify('+5 minutes')->format('Y-m-d H:i:s');
        insert(
            "INSERT INTO clarification(clarification_id,contest_id,asker_user_id,answerer_user_id,question,answer,clarification_status,is_public,asked_at,answered_at) VALUES(:id,:contest,:asker,:answerer,:question,:answer,:status,'Y',TO_TIMESTAMP_TZ(:asked,'YYYY-MM-DD HH24:MI:SS TZR'),CASE WHEN :is_open='Y' THEN NULL ELSE TO_TIMESTAMP_TZ(:answered,'YYYY-MM-DD HH24:MI:SS TZR') END)",
            ['id' => nextId('seq_clarification'), 'contest' => $contestIds[$reportContest],
             'asker' => $users[$reportAsker],
             'answerer' => $status === 'OPEN' ? null : $users[$contest['owner_report_id']],
             'question' => $question, 'answer' => $answer, 'status' => $status,
             'asked' => oracleTimestamp($asked), 'is_open' => $status === 'OPEN' ? 'Y' : 'N',
             'answered' => oracleTimestamp($answered)]
        );
    }
    verifyImport();
    return $contestIds;
}

try {
    if ($mode === '--verify') {
        $map = verifyImport();
        echo "Verified: five report contests and their related rows are present.\n";
    } else {
        $connection = Database::connection();
        $existing = existingImport();
        if (count($existing) === 5) {
            $map = verifyImport();
            echo "Already imported; no changes made.\n";
        } else {
            try {
                $map = doImport();
                if ($mode === '--apply') {
                    if (!oci_commit($connection)) throw new RuntimeException('Oracle COMMIT failed.');
                    echo "Import committed successfully.\n";
                } else {
                    oci_rollback($connection);
                    echo "Dry run passed; every imported row was rolled back.\n";
                }
            } catch (Throwable $error) {
                oci_rollback($connection);
                throw $error;
            }
        }
    }
    foreach ($map as $reportId => $pcmsId) {
        echo "Report contest {$reportId} => PCMS contest {$pcmsId}\n";
    }
} catch (Throwable $error) {
    fwrite(STDERR, "Import failed safely: {$error->getMessage()}\n");
    exit(1);
}
