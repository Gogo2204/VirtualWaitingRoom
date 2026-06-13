<?php $pageTitle = 'Room'; require_once __DIR__ . '/../partials/head.php'; ?>
<?php require_once __DIR__ . '/../partials/nav.php'; ?>

<div class="container-lg py-4">

    <!-- Back -->
    <a href="/rooms" class="btn btn-sm btn-outline-secondary mb-3">&larr; Rooms</a>

    <!-- Room heading -->
    <div class="mb-3">
        <h2 id="room-title" class="fw-bold mb-1"></h2>
        <p id="room-description" class="text-muted mb-2"></p>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span id="room-status-badge" class="sb"></span>
            <div id="teacher-controls" class="d-flex gap-1 flex-wrap" style="display:none!important">
                <button onclick="setStatus('open')"     class="btn btn-sm btn-outline-success">Open</button>
                <button onclick="setStatus('closed')"   class="btn btn-sm btn-outline-secondary">Close</button>
                <button onclick="setStatus('archived')" class="btn btn-sm btn-outline-danger">Archive</button>
            </div>
        </div>
    </div>

    <div id="msg" class="small text-danger mb-2"></div>

    <!-- Stats bar -->
    <div id="stats-bar" class="d-flex flex-wrap gap-4 mb-3 p-3 rounded border" style="display:none!important;background:var(--vwr-surface)">
        <div><div class="small text-muted">Waiting</div><strong id="stat-waiting">-</strong></div>
        <div><div class="small text-muted">In meeting</div><strong id="stat-meeting">-</strong></div>
        <div><div class="small text-muted">Served today</div><strong id="stat-served">-</strong></div>
        <div><div class="small text-muted">Avg queue</div><strong id="stat-avg-queue">-</strong></div>
        <div><div class="small text-muted">Avg meeting</div><strong id="stat-avg-meet">-</strong></div>
    </div>

    <!-- Teacher bulk actions -->
    <div id="bulk-actions" class="d-flex flex-wrap gap-2 align-items-center mb-3" style="display:none!important">
        <button onclick="inviteAllTemp()" class="btn btn-sm btn-outline-warning">Temp. invite all</button>
        <button onclick="returnAll()"     class="btn btn-sm btn-outline-secondary">Return all</button>
        <div class="d-flex gap-1 align-items-center">
            <input type="time" id="eta-all-input" class="form-control form-control-sm" style="width:110px">
            <button onclick="setEtaAll()" class="btn btn-sm btn-outline-primary">Set ETA for all</button>
        </div>
        <div class="d-flex gap-1 align-items-center">
            <input type="number" id="add-minutes-input" class="form-control form-control-sm" style="width:70px" min="1" value="5">
            <button onclick="addEtaMinutes()" class="btn btn-sm btn-outline-secondary">+min to ETA</button>
        </div>
    </div>

    <!-- Student controls -->
    <div id="student-controls" class="mb-3 d-flex gap-2" style="display:none!important">
        <button id="join-btn"  onclick="joinQueue()"  class="btn btn-sm btn-primary">Join Queue</button>
        <button id="leave-btn" onclick="leaveQueue()" class="btn btn-sm btn-outline-danger d-none">Leave Queue</button>
    </div>

    <div id="meeting-banner" class="alert alert-primary d-none mb-3">
        <strong>You are invited to a meeting.</strong>
        <a id="meeting-href" href="#" target="_blank" class="alert-link ms-2"></a>
    </div>

    <!-- Queue -->
    <div id="queue-container"><p class="text-muted small"><em>Loading…</em></p></div>
</div>

<?php require_once __DIR__ . '/../partials/app.js.php'; ?>
<script>
const roomId     = <?= (int)$roomId ?>;
const user       = requireAuth();
let   myItem     = null;
const today      = new Date().toISOString().slice(0, 10);
const openSlotIds = new Set();

const gradeEditIds = new Set();

const STATUS_LABEL = {
    waiting:      'Waiting',
    invited_temp: 'Temp. invited',
    invited_perm: 'In meeting',
    done:         'Done',
};

const ROOM_STATUS_LABEL = { open: 'Open', closed: 'Closed', archived: 'Archived' };

function sortQueue(queue) {
    const order = { waiting: 0, invited_temp: 1, invited_perm: 2, done: 3 };
    return [...queue].sort((a, b) => {
        const ao = order[a.status] ?? 4, bo = order[b.status] ?? 4;
        if (ao !== bo) return ao - bo;
        if (a.status === 'waiting') {
            if (a.eta && b.eta) return new Date(a.eta) - new Date(b.eta);
            if (a.eta) return -1;
            if (b.eta) return 1;
        }
        return a.position - b.position;
    });
}

function renderRoomHeader(room) {
    document.getElementById('room-title').textContent       = room.name;
    document.getElementById('room-description').textContent = room.description || '';
    const badge = document.getElementById('room-status-badge');
    badge.textContent = ROOM_STATUS_LABEL[room.status] ?? room.status;
    badge.className   = `sb sb-${room.status}`;
}

function buildComments(comments) {
    if (!comments?.length) return '';
    return '<div class="comment-list mt-1">'
        + comments.map(c => {
            const priv = c.visibility === 'teacher_only';
            return `<div class="comment-item${priv ? ' private' : ''}">
                <strong>${c.first_name} ${c.last_name}</strong>${priv ? ' <span class="sb sb-imported" style="font-size:.55rem;vertical-align:middle">private</span>' : ''}: ${c.content}
            </div>`;
        }).join('')
        + '</div>';
}

/**
 * Builds the grade cell content for a queue row.
 *
 * Visibility rules:
 *  - Grade display (static): visible to everyone once a grade exists.
 *  - Grade input: only teachers; only when status is 'invited_perm' or 'done';
 *    shown when there's no grade yet OR the teacher clicked "Edit".
 */
function buildGradeCell(item) {
    const hasGrade   = item.grade != null;
    const canGrade   = user.role === 'teacher'
                       && (item.status === 'invited_perm' || item.status === 'done');
    const inEditMode = gradeEditIds.has(item.id);
    const showInput  = canGrade && (!hasGrade || inEditMode);

    let html = '';

    if (hasGrade) {
        html += `<span class="grade-badge" id="grade-display-${item.id}">${parseFloat(item.grade).toFixed(2)}</span>`;
    }

    if (showInput) {
        const currentVal = hasGrade ? parseFloat(item.grade).toFixed(2) : '';
        html += `<div class="d-flex gap-1 align-items-center mt-1" id="grade-input-wrap-${item.id}">
            <input
                type="number"
                id="grade-input-${item.id}"
                class="form-control form-control-sm"
                style="width:80px"
                min="2" max="6" step="0.01"
                placeholder="2–6"
                value="${currentVal}"
            >
            <button class="btn btn-sm btn-outline-primary py-0 px-1" onclick="saveGrade(${item.id}, ${item.student_id})">✓</button>
            ${hasGrade
                ? `<button class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="cancelGradeEdit(${item.id})">✕</button>`
                : ''}
        </div>`;
    }

    if (canGrade && hasGrade && !inEditMode) {
        html += `<div class="mt-1"><button class="btn btn-sm btn-outline-secondary" onclick="startGradeEdit(${item.id})">Edit</button></div>`;
    }

    return html;
}

function buildActions(item) {
    let html = '';

    if (user.role === 'teacher') {
        if (item.status === 'waiting') {
            html += `<button class="btn btn-sm btn-outline-warning me-1" onclick="invite(${item.id},'temp')">Temp. invite</button>`;
            html += `<button class="btn btn-sm btn-primary me-1" onclick="invite(${item.id},'perm')">Invite to meeting</button>`;
        }
        if (item.status === 'invited_temp')
            html += `<button class="btn btn-sm btn-warning" onclick="studentReturn(${item.id})">Mark returned</button>`;
        if (item.status === 'invited_perm')
            html += `<button class="btn btn-sm btn-success" onclick="finishMeeting(${item.id})">Finish meeting</button>`;
        if (item.status !== 'done')
            html += `<div class="d-flex gap-1 mt-1">
                <input type="text" id="cmt-${item.id}" class="form-control form-control-sm" placeholder="Comment…" style="max-width:180px">
                <select id="vis-${item.id}" class="form-select form-select-sm w-auto">
                    <option value="teacher_only">Private</option>
                    <option value="public">Public</option>
                </select>
                <button class="btn btn-sm btn-outline-secondary" onclick="addComment(${item.id})">Add</button>
            </div>`;
    }

    if (user.role === 'student' && item.status !== 'done') {
        const myOwn = String(item.student_id) === String(user.id);
        const vis   = myOwn
            ? `<select id="vis-${item.id}" class="form-select form-select-sm w-auto">
                   <option value="public">Public</option>
                   <option value="teacher_only">Private</option>
               </select>`
            : '';
        html += `<div class="d-flex gap-1 mt-1">
            <input type="text" id="cmt-${item.id}" class="form-control form-control-sm"
                placeholder="${myOwn ? 'Add comment…' : 'Add public comment…'}" style="max-width:180px">
            ${vis}
            <button class="btn btn-sm btn-outline-secondary" onclick="addComment(${item.id})">Add</button>
        </div>`;
    }

    return html;
}

function renderQueue(queue) {
    const container = document.getElementById('queue-container');
    myItem = null;

    if (!queue.length) {
        container.innerHTML = '<p class="text-muted small">Queue is empty.</p>';
        updateStudentControls(null);
        return;
    }

    const sorted = sortQueue(queue);

    const rows = sorted.map((item, idx) => {
        if (String(item.student_id) === String(user.id)) myItem = item;

        let statusExtra = '';
        if (item.status === 'done' && item.times)
            statusExtra = `<br><small class="text-muted">Queue: ${fmtSeconds(item.times.queue_seconds)} · Meet: ${fmtSeconds(item.times.meeting_seconds)}</small>`;

        const etaDisplay = (item.eta && item.status === 'waiting')
            ? `<small>${new Date(item.eta).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})}</small>`
            : '';

        const etaSetBtn = (user.role === 'teacher' && item.status === 'waiting')
            ? `<div class="mt-1">
                <button class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="toggleSlot(${item.id})">Set</button>
                <span id="slot-wrap-${item.id}" class="d-inline-flex gap-1 align-items-center mt-1" style="display:none!important">
                    <input type="date" id="slot-date-${item.id}" class="form-control form-control-sm" value="${today}" style="width:130px">
                    <input type="time" id="slot-time-${item.id}" class="form-control form-control-sm" style="width:85px">
                    <button class="btn btn-sm btn-outline-primary py-0 px-1" onclick="setSlot(${item.id})">✓</button>
                </span>
               </div>`
            : '';

        return `<tr class="row-${item.status}" data-student-id="${item.student_id}">
            <td style="width:2.5rem">${idx + 1}</td>
            <td style="width:14rem;text-align:left">${item.first_name} ${item.last_name}</td>
            <td style="width:10rem"><span class="sb sb-${item.status}">${STATUS_LABEL[item.status] ?? item.status}</span>${statusExtra}</td>
            <td style="width:8rem">${etaDisplay}${etaSetBtn}</td>
            <td style="width:9rem">${buildGradeCell(item)}</td>
            <td style="text-align:left">${buildComments(item.comments)}${buildActions(item)}</td>
        </tr>`;
    }).join('');

    container.innerHTML = `<div class="table-responsive">
        <table class="table table-hover queue-table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:2.5rem">#</th>
                    <th style="width:14rem">Student</th>
                    <th style="width:10rem">Status</th>
                    <th style="width:6rem">ETA</th>
                    <th style="width:9rem">Grade</th>
                    <th>Comments</th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>
    </div>`;

    updateStudentControls(myItem);
}

function renderStats(s) {
    document.getElementById('stat-waiting').textContent   = s.currently_waiting;
    document.getElementById('stat-meeting').textContent   = s.currently_in_meeting;
    document.getElementById('stat-served').textContent    = s.students_served;
    document.getElementById('stat-avg-queue').textContent = fmtSeconds(s.avg_queue_seconds);
    document.getElementById('stat-avg-meet').textContent  = fmtSeconds(s.avg_meeting_seconds);
    document.getElementById('stats-bar').style.cssText    = 'display:flex!important';
}

function updateStudentControls(item) {
    if (user.role !== 'student') return;
    const canLeave = item?.status === 'waiting';
    const invited  = (item?.status === 'invited_perm' || item?.status === 'invited_temp') && item.meeting_link;

    document.getElementById('join-btn').classList.toggle('d-none', !!item);
    document.getElementById('leave-btn').classList.toggle('d-none', !canLeave);

    const banner = document.getElementById('meeting-banner');
    if (invited) {
        const a = document.getElementById('meeting-href');
        a.href = a.textContent = item.meeting_link;
        banner.classList.remove('d-none');
    } else {
        banner.classList.add('d-none');
    }
}

async function loadQueue() {
    try {
        const focusedId = document.activeElement?.id || null;
        const selStart  = document.activeElement?.selectionStart ?? null;
        const selEnd    = document.activeElement?.selectionEnd   ?? null;

        const saved = {};
        document.querySelectorAll('[id^="cmt-"]').forEach(el => {
            const id = el.id.slice(4);
            saved[id] = { text: el.value, vis: document.getElementById(`vis-${id}`)?.value ?? 'public' };
        });

        const savedGrades = {};
        document.querySelectorAll('[id^="grade-input-"]').forEach(el => {
            const id = el.id.slice('grade-input-'.length);
            if (el.value) savedGrades[id] = el.value;
        });

        const savedSlotDates = {};
        openSlotIds.forEach(id => {
            savedSlotDates[id] = document.getElementById(`slot-date-${id}`)?.value || today;
        });

        const data = await api('GET', `/api/rooms/${roomId}/queue`);
        renderRoomHeader(data.room);
        renderQueue(data.queue);

        Object.entries(saved).forEach(([id, { text, vis }]) => {
            const cmtEl = document.getElementById(`cmt-${id}`);
            const visEl = document.getElementById(`vis-${id}`);
            if (cmtEl && text) cmtEl.value = text;
            if (visEl) visEl.value = vis;
        });

        Object.entries(savedGrades).forEach(([id, val]) => {
            const el = document.getElementById(`grade-input-${id}`);
            if (el) el.value = val;
        });

        const bulkTime  = document.getElementById('eta-all-input')?.value || '';
        const toRemove  = [];
        openSlotIds.forEach(id => {
            const w = document.getElementById(`slot-wrap-${id}`);
            if (w) {
                w.style.cssText = 'display:inline-flex!important';
                const dateEl = document.getElementById(`slot-date-${id}`);
                const timeEl = document.getElementById(`slot-time-${id}`);
                if (dateEl) dateEl.value = savedSlotDates[id] || today;
                if (timeEl) timeEl.value = bulkTime;
            } else {
                toRemove.push(id);
            }
        });
        toRemove.forEach(id => openSlotIds.delete(id));

        if (focusedId) {
            const el = document.getElementById(focusedId);
            if (el) {
                el.focus();
                if (selStart !== null && el.setSelectionRange) {
                    try { el.setSelectionRange(selStart, selEnd); } catch {}
                }
            }
        }
    } catch (err) { setMsg('msg', err.message); }
}

async function loadStats() {
    try {
        const data = await api('GET', `/api/stats/rooms/${roomId}`);
        renderStats(data.stats);
    } catch { /* supplemental */ }
}


function startGradeEdit(itemId) {
    gradeEditIds.add(itemId);
    loadQueue();
}

function cancelGradeEdit(itemId) {
    gradeEditIds.delete(itemId);
    loadQueue();
}

async function saveGrade(itemId, studentId) {
    const input = document.getElementById(`grade-input-${itemId}`);
    if (!input) return;
    const raw = parseFloat(input.value);
    if (isNaN(raw) || raw < 2 || raw > 6) {
        setMsg('msg', 'Grade must be between 2 and 6.');
        return;
    }
    const grade = Math.round(raw * 100) / 100;
    try {
        await api('POST', `/api/rooms/${roomId}/grades`, { student_id: studentId, grade });
        gradeEditIds.delete(itemId);
        setMsg('msg', `Grade ${grade.toFixed(2)} saved.`, 'success');
        loadQueue();
    } catch (err) { setMsg('msg', err.message); }
}

function toggleSlot(itemId) {
    const id = String(itemId);
    const w  = document.getElementById(`slot-wrap-${id}`);
    if (!w) return;
    const opening = !openSlotIds.has(id);
    w.style.cssText = opening ? 'display:inline-flex!important' : 'display:none!important';
    if (opening) {
        openSlotIds.add(id);
        const bulkTime = document.getElementById('eta-all-input')?.value || '';
        const timeEl   = document.getElementById(`slot-time-${id}`);
        if (timeEl && bulkTime) timeEl.value = bulkTime;
    } else {
        openSlotIds.delete(id);
    }
}

async function setSlot(itemId) {
    const date = document.getElementById(`slot-date-${itemId}`)?.value;
    const time = document.getElementById(`slot-time-${itemId}`)?.value;
    if (!date || !time) return;
    try {
        await api('POST', `/api/rooms/${roomId}/queue/${itemId}/slot`, { datetime: `${date}T${time}` });
        loadQueue();
    } catch (err) { setMsg('msg', err.message); }
}

async function setEtaAll() {
    const time = document.getElementById('eta-all-input')?.value;
    if (!time) return;
    try {
        await api('POST', `/api/rooms/${roomId}/queue/set-eta-all`, { start_datetime: `${today}T${time}` });
        loadQueue();
    } catch (err) { setMsg('msg', err.message); }
}

async function addEtaMinutes() {
    const minutes = parseInt(document.getElementById('add-minutes-input')?.value);
    if (!minutes || minutes <= 0) return;
    try {
        await api('POST', `/api/rooms/${roomId}/queue/add-eta-minutes`, { minutes });
        loadQueue();
    } catch (err) { setMsg('msg', err.message); }
}

async function inviteAllTemp() {
    try { await api('POST', `/api/rooms/${roomId}/queue/invite-all-temp`); loadQueue(); loadStats(); }
    catch (err) { setMsg('msg', err.message); }
}

async function returnAll() {
    try { await api('POST', `/api/rooms/${roomId}/queue/return-all`); loadQueue(); loadStats(); }
    catch (err) { setMsg('msg', err.message); }
}

async function joinQueue() {
    try { await api('POST', `/api/rooms/${roomId}/queue`); loadQueue(); loadStats(); }
    catch (err) { setMsg('msg', err.message); }
}

async function leaveQueue() {
    if (!myItem) return;
    try { await api('DELETE', `/api/rooms/${roomId}/queue`, { room_item_id: myItem.id }); loadQueue(); loadStats(); }
    catch (err) { setMsg('msg', err.message); }
}

async function invite(itemId, mode) {
    try { await api('POST', `/api/rooms/${roomId}/queue/${itemId}/invite`, { mode }); loadQueue(); loadStats(); }
    catch (err) { setMsg('msg', err.message); }
}

async function finishMeeting(itemId) {
    try {
        const data = await api('POST', `/api/rooms/${roomId}/queue/${itemId}/finish`);
        const t    = data.times ?? {};
        setMsg('msg', `Done - Queue: ${fmtSeconds(t.queue_seconds)}, Meeting: ${fmtSeconds(t.meeting_seconds)}`, 'success');
        loadQueue(); loadStats();
    } catch (err) { setMsg('msg', err.message); }
}

async function studentReturn(itemId) {
    try { await api('POST', `/api/rooms/${roomId}/queue/${itemId}/return`); loadQueue(); loadStats(); }
    catch (err) { setMsg('msg', err.message); }
}

async function addComment(itemId) {
    const content    = document.getElementById(`cmt-${itemId}`)?.value?.trim();
    const visibility = document.getElementById(`vis-${itemId}`)?.value ?? 'public';
    if (!content) return;
    try { await api('POST', `/api/rooms/${roomId}/queue/${itemId}/comments`, { content, visibility }); loadQueue(); }
    catch (err) { setMsg('msg', err.message); }
}

async function setStatus(status) {
    try {
        await api('PATCH', `/api/rooms/${roomId}/status`, { status });
        const badge = document.getElementById('room-status-badge');
        badge.textContent = ROOM_STATUS_LABEL[status] ?? status;
        badge.className   = `sb sb-${status}`;
    } catch (err) { setMsg('msg', err.message); }
}

(async () => {
    if (user.role === 'teacher' || user.role === 'admin') {
        document.getElementById('teacher-controls').style.cssText = 'display:flex!important';
        document.getElementById('bulk-actions').style.cssText     = 'display:flex!important';
    } else {
        document.getElementById('student-controls').style.cssText = 'display:flex!important';
    }
    await loadQueue();
    loadStats();
    setInterval(() => { loadQueue(); loadStats(); }, 5000);
})();
</script>

<?php require_once __DIR__ . '/../partials/foot.php'; ?>