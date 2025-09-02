<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/csrf.php';
require_once __DIR__ . '/../lib/util.php';
Auth::start();
Auth::requireRole(['Admin','Teacher']);
$pdo = DB::conn();

function valid_isbn($isbn){ return preg_match('/^[0-9-]{10,13}$/', $isbn)===1; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'add_book') {
        $isbn = trim($_POST['isbn'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $copies = max(1, (int)($_POST['copies'] ?? 1));
        if ($isbn && valid_isbn($isbn) && $title && $author) {
            $pdo->prepare('INSERT INTO books (isbn,title,author,copies) VALUES (?,?,?,?)')->execute([$isbn,$title,$author,$copies]);
        } else { $error = 'Invalid ISBN or fields.'; }
    } elseif ($action === 'borrow') {
        $book_id = (int)($_POST['book_id'] ?? 0);
        $student_id = (int)($_POST['student_id'] ?? 0);
        $available = $pdo->prepare('SELECT copies - (SELECT COUNT(*) FROM book_loans WHERE book_id=? AND returned_at IS NULL) AS avail FROM books WHERE id=?');
        $available->execute([$book_id,$book_id]);
        $a = (int)$available->fetch()['avail'];
        if ($a > 0) {
            $pdo->prepare('INSERT INTO book_loans (book_id, student_id, borrowed_at) VALUES (?,?,CURDATE())')->execute([$book_id,$student_id]);
        } else { $error = 'No available copies.'; }
    } elseif ($action === 'return') {
        $loan_id = (int)($_POST['loan_id'] ?? 0);
        $pdo->prepare('UPDATE book_loans SET returned_at=CURDATE() WHERE id=? AND returned_at IS NULL')->execute([$loan_id]);
    }
}

$books = $pdo->query('SELECT * FROM books ORDER BY id DESC')->fetchAll();
$students = $pdo->query('SELECT id, first_name, last_name FROM students ORDER BY first_name')->fetchAll();
$loans = $pdo->query('SELECT bl.*, b.title, CONCAT(s.first_name, " ", s.last_name) AS student FROM book_loans bl JOIN books b ON b.id=bl.book_id JOIN students s ON s.id=bl.student_id ORDER BY bl.id DESC')->fetchAll();
$borrowedReport = $pdo->query('SELECT b.title, COUNT(bl.id) AS borrowed FROM books b LEFT JOIN book_loans bl ON bl.book_id=b.id AND bl.returned_at IS NULL GROUP BY b.id ORDER BY borrowed DESC')->fetchAll();

require_once __DIR__ . '/../partials/header.php';
?>
<h2>Library</h2>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<h3>Add Book</h3>
<form method="post" style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;align-items:end;">
  <input type="hidden" name="csrf_token" value="<?= e(CSRF::token()) ?>" />
  <input type="hidden" name="action" value="add_book" />
  <div class="field"><label>ISBN</label><input name="isbn" required /></div>
  <div class="field"><label>Title</label><input name="title" required /></div>
  <div class="field"><label>Author</label><input name="author" required /></div>
  <div class="field"><label>Copies</label><input type="number" name="copies" min="1" value="1" /></div>
  <div style="grid-column:1/5"><button class="btn" type="submit">Add</button></div>
  <small style="grid-column:1/5;color:#6b7280;">ISBN format: 10-13 digits, hyphens allowed.</small>
</form>

<h3>Borrow / Return</h3>
<form method="post" style="display:flex;gap:12px;align-items:end;">
  <input type="hidden" name="csrf_token" value="<?= e(CSRF::token()) ?>" />
  <input type="hidden" name="action" value="borrow" />
  <div class="field"><label>Book</label>
    <select name="book_id"><?php foreach ($books as $b): ?><option value="<?= (int)$b['id'] ?>"><?= e($b['title']) ?></option><?php endforeach; ?></select>
  </div>
  <div class="field"><label>Student</label>
    <select name="student_id"><?php foreach ($students as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['first_name'].' '.$s['last_name']) ?></option><?php endforeach; ?></select>
  </div>
  <button class="btn" type="submit">Borrow</button>
</form>

<h3>Loans</h3>
<table>
  <tr><th>Book</th><th>Student</th><th>Borrowed</th><th>Returned</th><th>Action</th></tr>
  <?php foreach ($loans as $l): ?>
    <tr>
      <td><?= e($l['title']) ?></td>
      <td><?= e($l['student']) ?></td>
      <td><?= e($l['borrowed_at']) ?></td>
      <td><?= e($l['returned_at']) ?></td>
      <td>
        <?php if (!$l['returned_at']): ?>
        <form method="post" style="display:inline;">
          <input type="hidden" name="csrf_token" value="<?= e(CSRF::token()) ?>" />
          <input type="hidden" name="action" value="return" />
          <input type="hidden" name="loan_id" value="<?= (int)$l['id'] ?>" />
          <button class="btn">Return</button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

<h3>Report: Borrowed Books</h3>
<table>
  <tr><th>Title</th><th>Borrowed (not returned)</th></tr>
  <?php foreach ($borrowedReport as $r): ?>
    <tr><td><?= e($r['title']) ?></td><td><?= (int)$r['borrowed'] ?></td></tr>
  <?php endforeach; ?>
</table>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
