<?php $pageTitle = 'Room Queue'; require_once __DIR__ . '/../partials/head.php'; ?>
<?php require_once __DIR__ . '/../partials/nav.php'; ?>

<div class="container-lg py-4">

    <!-- Header row -->
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <a href="/rooms" class="text-muted text-decoration-none small">← Rooms</a>
            <h4 id="room-title" class="fw-bold mb-0 mt-1">Queue</h4>
        </div>
        <div id="teacher-controls" class="d-flex flex-wrap gap-2 align-items-center" style="display:none!important">
            <div class="btn-group btn-group-sm">
                <button onclick="setStatus('open')"     class="btn btn-outline-success">Open</button>
                <button onclick="setStatus('closed')"   class="btn btn-outline-secondary">Close</button>
                <button onclick="setStatus('archived')" class="btn btn-outline-danger">Archive</button>
            </div>
            <button onclick="loadQueue()" class="btn btn-sm btn-outline-primary">↻ Refresh</button>
        </div>
    </div>

    <div id="msg" class="small text-danger mb-2"></div>

    <!-- Student join / leave -->
    <div id="student-controls" class="mb-3 d-flex gap-2" style="display:none!important">
        <button id="join-btn"  onclick="joinQueue()"  class="btn btn-sm btn-primary">Join Queue</button>
        <button id="leave-btn" onclick="leaveQueue()" class="btn btn-sm btn-outline-danger d-none">Leave Queue</button>
    </div>

    <!-- Meeting link banner -->
    <div id="meeting-banner" class="alert alert-primary d-none mb-3" role="alert">
        <strong>You are in a meeting.</strong>
        <a id="meeting-href" href="#" target="_blank" class="alert-link ms-2"></a>
    </div>

    <!-- Queue -->
    <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="small text-muted fw-semibold">Queue</span>
    </div>
    <div id="queue-container"><p class="text-muted small"><em>Loading…</em></p></div>
</div>

<?php require_once __DIR__ . '/../partials/app.js.php'; ?>
<script>
const roomId = <?= (int)$roomId ?>;
const user   = requireAuth();
let   myItem = null;

const STATUS_LABEL = {
    waiting:      'Waiting',
    invited_temp: 'Temp invited',
    invited_perm: 'In meeting',
    done:         'Done',
};

function buildComments(comments) {
    if (!comments?.length) return '';
    return '<div class="comment-list mt-1">'
        + comments.map(c => {
            const priv = c.visibility === 'teacher_only';
            return `<div class="comment-item${priv ? ' private' : ''}">
                <strong>${c.first_name} ${c.last_name}</strong>${priv ? ' <span class="badge text-bg-primary" style="font-size:.6rem">private</span>' : ''}: ${c.content}
            </div>`;
        }).join('')
        + '</div>';
}

function buildActions(item) {
    let html = '';

    if (user.role === 'teacher') {
        if (item.status === 'waiting') {
            html += `<button class="btn btn-sm btn-outline-primary me-1" onclick="invite(${item.id},'temp')">Temp</button>`;
            html += `<button class="btn btn-sm btn-primary me-1" onclick="invite(${item.id},'perm')">Invite</button>`;
            html += `<input type="datetime-local" id="slot-${item.id}" class="form-control form-control-sm d-inline-block w-auto me-1">`;
            html += `<button class="btn btn-sm btn-outline-secondary" onclick="setSlot(${item.id})">Set slot</button>`;
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

    if (!queue.length) {
        container.innerHTML = '<p class="text-muted small">Queue is empty.</p>';
        updateStudentControls(null);
        return;
    }

    const rows = queue.map(item => {
        if (String(item.student_id) === String(user.id)) myItem = item;

        let statusExtra = '';
        if (item.status === 'done' && item.times)
            statusExtra = `<br><small class="text-muted">Queue: ${fmtSeconds(item.times.queue_seconds)} · Meeting: ${fmtSeconds(item.times.meeting_seconds)}</small>`;

        const eta = (item.eta && item.status === 'waiting')
            ? `<small>${new Date(item.eta).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})}</small>`
            : '';

        return `<tr class="row-${item.status}">
            <td class="text-center fw-bold text-muted" style="width:3rem">${item.position}</td>
            <td>${item.first_name} ${item.last_name}</td>
            <td><span class="sb sb-${item.status}">${STATUS_LABEL[item.status] ?? item.status}</span>${statusExtra}</td>
            <td>${eta}</td>
            <td>${buildComments(item.comments)}${buildActions(item)}</td>
        </tr>`;
    }).join('');

    container.innerHTML = `<div class="table-responsive">
        <table class="table table-hover queue-table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th><th>Student</th><th>Status</th><th>ETA</th><th>Comments / Actions</th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>
    </div>`;

    updateStudentControls(myItem);
}

function updateStudentControls(item) {
    if (user.role !== 'student') return;
    const canLeave = item?.status === 'waiting';
    const inMeet   = item?.status === 'invited_perm' && item.meeting_link;

    document.getElementById('join-btn').classList.toggle('d-none', !!item);
    document.getElementById('leave-btn').classList.toggle('d-none', !canLeave);

    const banner = document.getElementById('meeting-banner');
    if (inMeet) {
        const a = document.getElementById('meeting-href');
        a.href = a.textContent = item.meeting_link;
        banner.classList.remove('d-none');
    } else {
        banner.classList.add('d-none');
    }
}

async function loadQueue() {
    try {
        // Preserve comment inputs and visibility dropdowns before re-render
        const saved = {};
        document.querySelectorAll('[id^="cmt-"]').forEach(el => {
            const id = el.id.slice(4);
            saved[id] = { text: el.value, vis: document.getElementById(`vis-${id}`)?.value ?? 'public' };
        });

        const data = await api('GET', `/api/rooms/${roomId}/queue`);
        myItem = null;
        renderQueue(data.queue);

        // Restore preserved state
        Object.entries(saved).forEach(([id, { text, vis }]) => {
            const cmtEl = document.getElementById(`cmt-${id}`);
            const visEl = document.getElementById(`vis-${id}`);
            if (cmtEl && text) cmtEl.value = text;
            if (visEl) visEl.value = vis;
        });
    } catch (err) { setMsg('msg', err.message); }
}

async function joinQueue() {
    try { await api('POST', `/api/rooms/${roomId}/queue`); loadQueue(); }
    catch (err) { setMsg('msg', err.message); }
}

async function leaveQueue() {
    if (!myItem) return;
    try { await api('DELETE', `/api/rooms/${roomId}/queue`, { room_item_id: myItem.id }); loadQueue(); }
    catch (err) { setMsg('msg', err.message); }
}

async function invite(itemId, mode) {
    try {
        const data = await api('POST', `/api/rooms/${roomId}/queue/${itemId}/invite`, { mode });
        setMsg('msg', mode === 'perm' ? `Invited. Link: ${data.meeting_link || '—'}` : '', 'success');
        loadQueue();
    } catch (err) { setMsg('msg', err.message); }
}

async function finishMeeting(itemId) {
    try {
        const data = await api('POST', `/api/rooms/${roomId}/queue/${itemId}/finish`);
        const t    = data.times ?? {};
        setMsg('msg', `Done — Queue: ${fmtSeconds(t.queue_seconds)}, Meeting: ${fmtSeconds(t.meeting_seconds)}`, 'success');
        loadQueue();
    } catch (err) { setMsg('msg', err.message); }
}

async function studentReturn(itemId) {
    try { await api('POST', `/api/rooms/${roomId}/queue/${itemId}/return`); loadQueue(); }
    catch (err) { setMsg('msg', err.message); }
}

async function setSlot(itemId) {
    const val = document.getElementById(`slot-${itemId}`)?.value;
    if (!val) return;
    try { await api('POST', `/api/rooms/${roomId}/queue/${itemId}/slot`, { datetime: val }); loadQueue(); }
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
    try { await api('PATCH', `/api/rooms/${roomId}/status`, { status }); loadQueue(); }
    catch (err) { setMsg('msg', err.message); }
}

(async () => {
    if (user.role === 'teacher' || user.role === 'admin') {
        document.getElementById('teacher-controls').style.cssText = 'display:flex!important';
    } else {
        document.getElementById('student-controls').style.cssText = 'display:flex!important';
    }
    await loadQueue();
    setInterval(loadQueue, 5000);
})();
</script>

<?php require_once __DIR__ . '/../partials/foot.php'; ?>
