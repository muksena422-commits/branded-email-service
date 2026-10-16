
<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

requireLogin();

$pageTitle = 'Exam Results';
$activePage = 'results';

$stats = [
    'total' => 0,
    'average' => 0,
    'passed' => 0,
    'failed' => 0
];

$results = [];
$error = '';

try {
    $statsQuery = $pdo->query("
        SELECT
            COUNT(*) AS total,
            COALESCE(ROUND(AVG(percentage), 2), 0) AS average,
            COALESCE(SUM(CASE WHEN result_status = 'Pass' THEN 1 ELSE 0 END), 0) AS passed,
            COALESCE(SUM(CASE WHEN result_status = 'Fail' THEN 1 ELSE 0 END), 0) AS failed
        FROM exam_results
    ");

    $stats = $statsQuery->fetch(PDO::FETCH_ASSOC);

    $search = trim((string)($_GET['search'] ?? ''));

    if ($search !== '') {
        $stmt = $pdo->prepare("
            SELECT
                er.id,
                er.exam_name,
                er.score,
                er.total_marks,
                er.percentage,
                er.result_status,
                er.submitted_at,
                s.name AS student_name
            FROM exam_results er
            INNER JOIN students s ON s.id = er.student_id
            WHERE er.exam_name LIKE ?
               OR s.name LIKE ?
            ORDER BY er.submitted_at DESC
        ");

        $term = '%' . $search . '%';
        $stmt->execute([$term, $term]);
    } else {
        $stmt = $pdo->query("
            SELECT
                er.id,
                er.exam_name,
                er.score,
                er.total_marks,
                er.percentage,
                er.result_status,
                er.submitted_at,
                s.name AS student_name
            FROM exam_results er
            INNER JOIN students s ON s.id = er.student_id
            ORDER BY er.submitted_at DESC
        ");
    }

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    error_log('Exam results dashboard error: ' . $e->getMessage());
    $error = 'Unable to load exam results. Please check the database table and student table structure.';
}

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
.results-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 24px;
}
.results-heading h1 {
    margin: 0 0 6px;
    font-size: 28px;
}
.results-heading p {
    margin: 0;
    color: #64748b;
}
.result-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.result-stat {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 20px;
}
.result-stat .stat-label {
    color: #64748b;
    font-size: 13px;
    margin-bottom: 10px;
}
.result-stat .stat-value {
    font-size: 28px;
    font-weight: 800;
    color: #0f172a;
}
.results-panel {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 20px;
}
.results-toolbar {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    align-items: center;
    margin-bottom: 18px;
}
.results-search {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.results-search input {
    padding: 10px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 9px;
    min-width: 240px;
}
.results-table-wrap {
    overflow-x: auto;
}
.results-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 760px;
}
.results-table th,
.results-table td {
    padding: 13px 12px;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
    font-size: 14px;
}
.results-table th {
    color: #64748b;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .04em;
}
.status-pill {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
}
.status-pass {
    background: #dcfce7;
    color: #166534;
}
.status-fail {
    background: #fee2e2;
    color: #991b1b;
}
.empty-results {
    text-align: center !important;
    padding: 32px !important;
    color: #64748b;
}
@media (max-width: 850px) {
    .result-stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
@media (max-width: 480px) {
    .result-stats {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="results-heading">
    <div>
        <h1>Exam Results</h1>
        <p>View student exam performance and submission history.</p>
    </div>
</div>

<?php if ($error !== ''): ?>
    <div class="alert alert-error"><?= h($error) ?></div>
<?php else: ?>

<div class="result-stats">
    <div class="result-stat">
        <div class="stat-label">Total Attempts</div>
        <div class="stat-value"><?= (int)$stats['total'] ?></div>
    </div>

    <div class="result-stat">
        <div class="stat-label">Average Percentage</div>
        <div class="stat-value"><?= number_format((float)$stats['average'], 2) ?>%</div>
    </div>

    <div class="result-stat">
        <div class="stat-label">Passed</div>
        <div class="stat-value"><?= (int)$stats['passed'] ?></div>
    </div>

    <div class="result-stat">
        <div class="stat-label">Failed</div>
        <div class="stat-value"><?= (int)$stats['failed'] ?></div>
    </div>
</div>

<div class="results-panel">
    <div class="results-toolbar">
        <div>
            <h2 style="margin:0 0 5px;">Student Results</h2>
            <p style="margin:0;color:#64748b;font-size:13px;">
                <?= count($results) ?> record(s) displayed
            </p>
        </div>

        <form class="results-search" method="get" action="exam_results.php">
            <input
                type="text"
                name="search"
                placeholder="Search student or exam..."
                value="<?= h($search ?? '') ?>"
            >
            <button class="btn btn-primary" type="submit">
                <i class="fa-solid fa-magnifying-glass"></i> Search
            </button>
            <?php if (!empty($search)): ?>
                <a class="btn btn-ghost" href="exam_results.php">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="results-table-wrap">
        <table class="results-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Exam</th>
                    <th>Score</th>
                    <th>Percentage</th>
                    <th>Status</th>
                    <th>Submitted At</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($results)): ?>
                    <tr>
                        <td colspan="7" class="empty-results">
                            No exam results found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($results as $index => $result): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= h($result['student_name'] ?? 'Unknown') ?></td>
                            <td><?= h($result['exam_name']) ?></td>
                            <td>
                                <?= number_format((float)$result['score'], 2) ?>
                                /
                                <?= number_format((float)$result['total_marks'], 2) ?>
                            </td>
                            <td><?= number_format((float)$result['percentage'], 2) ?>%</td>
                            <td>
                                <span class="status-pill <?= $result['result_status'] === 'Pass' ? 'status-pass' : 'status-fail' ?>">
                                    <?= h($result['result_status']) ?>
                                </span>
                            </td>
                            <td><?= h($result['submitted_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</main>
</body>
</html>
