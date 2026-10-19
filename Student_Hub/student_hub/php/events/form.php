<?php
declare(strict_types=1);

/* =========================================================
   ADD / EDIT EVENT  -  INSERT / UPDATE + poster upload
   form.php        -> add
   form.php?id=5   -> edit event 5
   Poster rules: JPG / PNG / WEBP only, max 2 MB, checked on
   the server (extension + real MIME type + image decode).
   ========================================================= */

require_once __DIR__ . '/_bootstrap.php';

$id     = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$isEdit = $id > 0;

$data = [
    'title' => '', 'description' => '', 'event_date' => '', 'event_time' => '',
    'location' => '', 'category' => '', 'status' => 'Upcoming',
];
$errors = [];
$existing = null;

if ($isEdit) {
    $existing = fetch_event($conn, $id);
    if (!$existing) {
        flash('error', 'Event not found. It may have been deleted already.');
        header('Location: index.php');
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $data = array_intersect_key($existing, $data);
        $data['event_time'] = substr((string) $data['event_time'], 0, 5);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        http_response_code(400);
        exit('Invalid or expired form token. Go back, refresh the page and try again.');
    }

    // If the file was bigger than post_max_size, PHP drops ALL form fields.
    if (empty($_POST) && ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        http_response_code(413);
        exit('The upload was too large. Go back and choose a smaller poster (max 2 MB).');
    }

    [$data, $errors] = validate_event($_POST);

    // 1) validate the poster BEFORE touching the database
    [$tmpPath, $ext, $posterError] = check_poster($_FILES['poster'] ?? null);
    if ($posterError) {
        $errors['poster'] = $posterError;
    }

    if (!$errors) {
        $newPoster = null;
        $oldPoster = $existing['poster'] ?? null;
        $removeOld = $isEdit && isset($_POST['remove_poster']);

        // 2) save the file
        if ($tmpPath !== null) {
            $newPoster = save_poster($tmpPath, $ext);
            if ($newPoster === null) {
                $errors['poster'] = 'The poster could not be saved on the server. Check the uploads folder permissions.';
            }
        }
    }

    if (!$errors) {
        // keep the old poster unless replaced or removed
        $posterValue = $oldPoster;
        if ($newPoster !== null)  { $posterValue = $newPoster; }
        elseif ($removeOld)       { $posterValue = null; }

        try {
            if ($isEdit) {
                /* ---------- UPDATE ---------- */
                $stmt = $conn->prepare(
                    'UPDATE events
                     SET title = ?, description = ?, event_date = ?, event_time = ?,
                         location = ?, category = ?, status = ?, poster = ?
                     WHERE id = ?'
                );
                $stmt->bind_param(
                    'ssssssssi',
                    $data['title'], $data['description'], $data['event_date'], $data['event_time'],
                    $data['location'], $data['category'], $data['status'], $posterValue, $id
                );
                $stmt->execute();
                $stmt->close();

                if ($posterValue !== $oldPoster) {
                    delete_poster_file($oldPoster);          // old file no longer used
                }
                audit_log($conn, 'UPDATE', 'event', $id, 'Updated event "' . $data['title'] . '"');
                flash('success', 'Event "' . $data['title'] . '" was updated successfully.');
            } else {
                /* ---------- INSERT ---------- */
                $stmt = $conn->prepare(
                    'INSERT INTO events (title, description, event_date, event_time, location, category, status, poster)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->bind_param(
                    'ssssssss',
                    $data['title'], $data['description'], $data['event_date'], $data['event_time'],
                    $data['location'], $data['category'], $data['status'], $posterValue
                );
                $stmt->execute();
                $stmt->close();
                audit_log($conn, 'CREATE', 'event', (int) $conn->insert_id, 'Added event "' . $data['title'] . '"');
                flash('success', 'Event "' . $data['title'] . '" was added successfully.');
            }
            header('Location: index.php');
            exit;
        } catch (mysqli_sql_exception $ex) {
            delete_poster_file($newPoster);                  // do not leave an orphan file behind
            if ((int) $ex->getCode() === 1062) {
                $errors['title'] = 'An event with this title already exists on this date.';
            } else {
                error_log('Event save failed: ' . $ex->getMessage());
                $errors['_form'] = 'Could not save the event. Please try again.'
                    . ($mysqliDebug ? ' (' . $ex->getMessage() . ')' : '');
            }
        }
    }
    $data['event_time'] = substr((string) $data['event_time'], 0, 5);
}

function ferr(array $errors, string $key): string
{
    return isset($errors[$key]) ? '<div class="invalid-feedback d-block">' . e($errors[$key]) . '</div>' : '';
}
function cls(array $errors, string $key): string
{
    return isset($errors[$key]) ? ' is-invalid' : '';
}

$title = $isEdit ? 'Edit Event' : 'Add Event';
events_header($title);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h3 mb-0"><?= e($title) ?></h2>
    <a class="btn btn-outline-secondary" href="index.php">&laquo; Back to list</a>
</div>

<?php if (isset($errors['_form'])): ?>
    <div class="alert alert-danger" role="alert"><?= e($errors['_form']) ?></div>
<?php elseif ($errors): ?>
    <div class="alert alert-danger" role="alert">Please fix the highlighted fields and submit again.</div>
<?php endif; ?>

<form class="card card-body shadow-sm" method="post" enctype="multipart/form-data"
      action="form.php<?= $isEdit ? '?id=' . $id : '' ?>" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="MAX_FILE_SIZE" value="<?= POSTER_MAX_BYTES ?>">
    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label" for="title">Title</label>
            <input class="form-control<?= cls($errors, 'title') ?>" type="text" id="title" name="title" maxlength="150" required value="<?= e($data['title']) ?>">
            <?= ferr($errors, 'title') ?>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="category">Category</label>
            <select class="form-select<?= cls($errors, 'category') ?>" id="category" name="category" required>
                <option value="">Select category</option>
                <?php foreach (EVENT_CATEGORIES as $c): ?>
                    <option value="<?= e($c) ?>" <?= $data['category'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                <?php endforeach; ?>
            </select>
            <?= ferr($errors, 'category') ?>
        </div>

        <div class="col-12">
            <label class="form-label" for="description">Description</label>
            <textarea class="form-control<?= cls($errors, 'description') ?>" id="description" name="description" rows="4" maxlength="2000" required><?= e($data['description']) ?></textarea>
            <?= ferr($errors, 'description') ?>
        </div>

        <div class="col-md-3">
            <label class="form-label" for="event_date">Date</label>
            <input class="form-control<?= cls($errors, 'event_date') ?>" type="date" id="event_date" name="event_date" required value="<?= e($data['event_date']) ?>">
            <?= ferr($errors, 'event_date') ?>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="event_time">Time</label>
            <input class="form-control<?= cls($errors, 'event_time') ?>" type="time" id="event_time" name="event_time" required value="<?= e($data['event_time']) ?>">
            <?= ferr($errors, 'event_time') ?>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="location">Location</label>
            <input class="form-control<?= cls($errors, 'location') ?>" type="text" id="location" name="location" maxlength="150" required value="<?= e($data['location']) ?>">
            <?= ferr($errors, 'location') ?>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="status">Status</label>
            <select class="form-select<?= cls($errors, 'status') ?>" id="status" name="status" required>
                <?php foreach (EVENT_STATUSES as $s): ?>
                    <option value="<?= e($s) ?>" <?= $data['status'] === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                <?php endforeach; ?>
            </select>
            <?= ferr($errors, 'status') ?>
        </div>

        <div class="col-12">
            <label class="form-label" for="poster">Event poster <span class="text-secondary">(optional, JPG / PNG / WEBP, max 2 MB)</span></label>
            <input class="form-control<?= cls($errors, 'poster') ?>" type="file" id="poster" name="poster"
                   accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
            <?= ferr($errors, 'poster') ?>
            <div id="posterClientMsg" class="invalid-feedback"></div>

            <?php if ($isEdit && $existing['poster']): ?>
                <div class="mt-3">
                    <div class="small text-secondary mb-1">Current poster</div>
                    <img class="poster-preview border" src="<?= e(POSTER_URL . $existing['poster']) ?>" alt="Current poster">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="remove_poster" name="remove_poster" value="1">
                        <label class="form-check-label" for="remove_poster">Remove current poster</label>
                    </div>
                    <div class="form-text">Choosing a new file above replaces the current poster.</div>
                </div>
            <?php endif; ?>
            <img id="posterPreview" class="poster-preview border mt-3 d-none" alt="Selected poster preview">
        </div>

        <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Update Event' : 'Add Event' ?></button>
            <a class="btn btn-outline-secondary" href="index.php">Cancel</a>
        </div>
    </div>
</form>

<script>
/* Friendly early check in the browser. The server repeats every check, so this is only a convenience. */
(function () {
    const input = document.getElementById('poster');
    const msg = document.getElementById('posterClientMsg');
    const preview = document.getElementById('posterPreview');
    const MAX = <?= POSTER_MAX_BYTES ?>;
    const OK = ['image/jpeg', 'image/png', 'image/webp'];

    input.addEventListener('change', function () {
        const f = input.files[0];
        input.classList.remove('is-invalid');
        msg.textContent = '';
        preview.classList.add('d-none');
        if (!f) return;

        let problem = '';
        if (OK.indexOf(f.type) === -1) problem = 'Only JPG, PNG or WEBP images are allowed.';
        else if (f.size > MAX) problem = 'Poster is too large. Maximum size is 2 MB.';

        if (problem) {
            input.value = '';
            input.classList.add('is-invalid');
            msg.textContent = problem;
            msg.classList.add('d-block');
            return;
        }
        preview.src = URL.createObjectURL(f);
        preview.classList.remove('d-none');
    });
})();
</script>

<?php events_footer();
