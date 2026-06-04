document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    const classId = Number(params.get('class_id') || 0);

    const classNameEl = document.getElementById('membersClassName');
    const tabPosts = document.getElementById('tabPosts');
    const tabMembers = document.getElementById('tabMembers');
    const tabReports = document.getElementById('tabReports');

    const searchInput = document.getElementById('studentSearchInput');
    const searchResults = document.getElementById('studentSearchResults');
    const pendingList = document.getElementById('pendingMembersList');
    const approvedList = document.getElementById('approvedMembersList');
    const copyInviteLinkBtn = document.getElementById('copyInviteLinkBtn');
    const inviteLinkStatus = document.getElementById('inviteLinkStatus');
    let actionToastTimer = null;

    if (!classId) {
        alert('Missing class reference.');
        window.location.href = './classes.html';
        return;
    }

    tabPosts.href = `./class-handling.html?class_id=${encodeURIComponent(classId)}`;
    tabMembers.href = `./student_members.html?class_id=${encodeURIComponent(classId)}`;
    tabReports.href = `./class_reports.html?class_id=${encodeURIComponent(classId)}`;

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function buildClassDisplay(item) {
        const className = String(item.class_name || '').trim();
        const term = String(item.term_number || '').trim();
        const startYear = String(item.start_year || '').trim();
        const endYear = String(item.end_year || '').trim();
        if (/term\s*\d+/i.test(className) || (startYear && endYear && className.includes(`${startYear}-${endYear}`))) {
            return className;
        }
        return `${className} Term ${term} ${startYear}-${endYear}`.trim();
    }

    function ensureActionToast() {
        let toast = document.getElementById('membersActionToast');
        if (toast) return toast;

        toast = document.createElement('div');
        toast.id = 'membersActionToast';
        toast.className = 'members-action-toast hidden';
        document.body.appendChild(toast);
        return toast;
    }

    function showActionToast(message, type = 'success') {
        const toast = ensureActionToast();
        toast.textContent = message;
        toast.classList.remove('hidden', 'success', 'error');
        toast.classList.add(type === 'error' ? 'error' : 'success');

        if (actionToastTimer) {
            clearTimeout(actionToastTimer);
        }

        actionToastTimer = setTimeout(() => {
            toast.classList.add('hidden');
        }, 2200);
    }

    function markActionButtonProcessing(button, options = {}) {
        if (!button) return;
        const hasText = options.hasText !== false;
        button.dataset.originalHtml = button.innerHTML;
        button.dataset.originalText = button.textContent;
        button.disabled = true;
        button.classList.add('is-loading');

        if (hasText) {
            button.textContent = options.processingText || 'Processing...';
        } else {
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        }
    }

    function markActionButtonDone(button, options = {}) {
        if (!button) return;
        const hasText = options.hasText !== false;
        button.classList.remove('is-loading');
        button.classList.add('is-done');

        if (hasText) {
            button.textContent = options.doneText || 'Done';
        } else {
            button.innerHTML = '<i class="fas fa-check"></i>';
        }
    }

    function restoreActionButton(button) {
        if (!button) return;
        button.classList.remove('is-loading', 'is-done');
        button.disabled = false;

        if (button.dataset.originalHtml) {
            button.innerHTML = button.dataset.originalHtml;
        } else if (button.dataset.originalText) {
            button.textContent = button.dataset.originalText;
        }
    }

    function wait(ms) {
        return new Promise((resolve) => setTimeout(resolve, ms));
    }

    function setInviteLinkStatus(message, isError = false) {
        if (!inviteLinkStatus) return;
        inviteLinkStatus.textContent = message;
        inviteLinkStatus.classList.toggle('is-error', isError);
        inviteLinkStatus.classList.toggle('is-success', !isError && message !== '');
    }

    async function handleCopyInvite() {
        if (!copyInviteLinkBtn) return;

        try {
            copyInviteLinkBtn.disabled = true;
            copyInviteLinkBtn.classList.add('is-loading');
            setInviteLinkStatus('Generating...');

            const body = new URLSearchParams({ class_id: String(classId) });
            const response = await fetch('create_invite_link.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: body.toString()
            });
            const payload = await response.json();

            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'Unable to generate invite.');
            }

            const className = (classNameEl && classNameEl.textContent) ? classNameEl.textContent.trim() : 'Class';
            const inviteUrl = String(payload.inviteUrl);
            const htmlLink = `<a href="${inviteUrl}">${className}</a>`;
            
            if (navigator.clipboard && window.ClipboardItem) {
                const blobHtml = new Blob([htmlLink], { type: 'text/html' });
                const blobText = new Blob([inviteUrl], { type: 'text/plain' });
                const item = new ClipboardItem({
                    'text/html': blobHtml,
                    'text/plain': blobText
                });
                await navigator.clipboard.write([item]);
            } else if (navigator.clipboard && navigator.clipboard.writeText) {
                await navigator.clipboard.writeText(inviteUrl);
            } else {
                window.prompt('Copy this invite link:', inviteUrl);
            }

            setInviteLinkStatus(`Hyperlink for ${className} copied.`);
        } catch (error) {
            setInviteLinkStatus(error.message || 'Failed to copy invite link.', true);
            alert(error.message || 'Failed to copy invite link.');
        } finally {
            copyInviteLinkBtn.classList.remove('is-loading');
            copyInviteLinkBtn.disabled = false;
        }
    }

    function createMemberCard(item, mode) {
        const wrapper = document.createElement('article');
        wrapper.className = 'member-card';

        const fullName = `${item.firstName || ''} ${item.lastName || ''}`.trim() || 'Student';
        const idNumber = item.idNumber || 'No ID';
        const statusLabel = mode === 'pending' ? 'Pending' : 'Member';

        wrapper.innerHTML = `
            <div class="member-avatar"><i class="fas fa-user"></i></div>
            <div class="member-info">
                <p class="member-name">${escapeHtml(fullName)}</p>
                <p class="member-meta">${escapeHtml(idNumber)}</p>
                <p class="member-meta">${escapeHtml(item.email || '')}</p>
            </div>
            <div class="member-actions-right">
                <span class="member-status-badge ${mode === 'pending' ? 'is-pending' : 'is-approved'}">${statusLabel}</span>
                <div class="member-actions-btns"></div>
            </div>
        `;

        const btnsWrap = wrapper.querySelector('.member-actions-btns');

        if (mode === 'pending') {
            const approveBtn = document.createElement('button');
            approveBtn.type = 'button';
            approveBtn.className = 'member-action-btn approve';
            approveBtn.innerHTML = '<i class="fas fa-check"></i>';
            approveBtn.title = 'Approve request';
            approveBtn.addEventListener('click', async () => {
                try {
                    markActionButtonProcessing(approveBtn, { hasText: false });
                    await postForm('approve_student_membership.php', {
                        class_id: classId,
                        student_id: item.studentId
                    });
                    markActionButtonDone(approveBtn, { hasText: false });
                    showActionToast('Enrollment request approved.');
                    await wait(450);
                    await loadMembers();
                } catch (error) {
                    restoreActionButton(approveBtn);
                    showActionToast(error.message || 'Failed to approve request.', 'error');
                    alert(error.message || 'Failed to approve request.');
                }
            });

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'member-action-btn remove';
            removeBtn.innerHTML = '<i class="fas fa-times"></i>';
            removeBtn.title = 'Remove request';
            removeBtn.addEventListener('click', async () => {
                if (!confirm('Remove this pending student from the class queue?')) return;
                try {
                    markActionButtonProcessing(removeBtn, { hasText: false });
                    await postForm('remove_student_member.php', {
                        class_id: classId,
                        student_id: item.studentId
                    });
                    markActionButtonDone(removeBtn, { hasText: false });
                    showActionToast('Pending request removed.');
                    await wait(450);
                    await loadMembers();
                } catch (error) {
                    restoreActionButton(removeBtn);
                    showActionToast(error.message || 'Failed to remove pending request.', 'error');
                    alert(error.message || 'Failed to remove pending request.');
                }
            });

            btnsWrap.appendChild(approveBtn);
            btnsWrap.appendChild(removeBtn);
        } else {
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'member-action-btn remove';
            removeBtn.innerHTML = '<i class="fas fa-user-minus"></i>';
            removeBtn.title = 'Remove member';
            removeBtn.addEventListener('click', async () => {
                if (!confirm('Remove this student from class members?')) return;
                try {
                    markActionButtonProcessing(removeBtn, { hasText: false });
                    await postForm('remove_student_member.php', {
                        class_id: classId,
                        student_id: item.studentId
                    });
                    markActionButtonDone(removeBtn, { hasText: false });
                    showActionToast('Student removed from class members.');
                    await wait(450);
                    await loadMembers();
                } catch (error) {
                    restoreActionButton(removeBtn);
                    showActionToast(error.message || 'Failed to remove class member.', 'error');
                    alert(error.message || 'Failed to remove class member.');
                }
            });
            btnsWrap.appendChild(removeBtn);
        }

        return wrapper;
    }

    if (copyInviteLinkBtn) {
        copyInviteLinkBtn.addEventListener('click', () => {
            handleCopyInvite();
        });
    }

    async function postForm(url, payload) {
        const body = new URLSearchParams(payload);
        const response = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString()
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Request failed.');
        }
        return data;
    }

    async function loadClassHeader() {
        try {
            const response = await fetch('fetch_classes.php', { credentials: 'same-origin' });
            const data = await response.json();
            if (!response.ok || !data.success || !Array.isArray(data.classes)) return;
            const target = data.classes.find((entry) => Number(entry.class_id) === classId);
            if (target && classNameEl) {
                classNameEl.textContent = buildClassDisplay(target);
            }
        } catch (error) {
            console.warn('Unable to load class header:', error);
        }
    }

    async function loadMembers() {
        const response = await fetch(`fetch_class_members.php?class_id=${encodeURIComponent(classId)}`, {
            credentials: 'same-origin'
        });
        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Failed to load class members.');
        }

        pendingList.innerHTML = '';
        approvedList.innerHTML = '';

        if (!Array.isArray(data.pending) || data.pending.length === 0) {
            pendingList.innerHTML = '<p class="members-empty">No pending requests.</p>';
        } else {
            data.pending.forEach((item) => {
                pendingList.appendChild(createMemberCard(item, 'pending'));
            });
        }

        if (!Array.isArray(data.members) || data.members.length === 0) {
            approvedList.innerHTML = '<p class="members-empty">No class members yet.</p>';
        } else {
            data.members.forEach((item) => {
                approvedList.appendChild(createMemberCard(item, 'approved'));
            });
        }
    }

    let searchTimer = null;

    async function loadSearchResults(keyword) {
        if (keyword.trim() === '') {
            searchResults.innerHTML = '<p class="members-empty">Type to search students.</p>';
            return;
        }

        const response = await fetch(`search_students_for_class.php?class_id=${encodeURIComponent(classId)}&q=${encodeURIComponent(keyword)}`, {
            credentials: 'same-origin'
        });
        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Failed to search students.');
        }

        searchResults.innerHTML = '';

        if (!Array.isArray(data.students) || data.students.length === 0) {
            searchResults.innerHTML = '<p class="members-empty">No matching students found.</p>';
            return;
        }

        data.students.forEach((student) => {
            const fullName = `${student.firstName || ''} ${student.lastName || ''}`.trim() || 'Student';
            const status = String(student.currentMembershipStatus || '').toLowerCase();

            const row = document.createElement('div');
            row.className = 'student-search-row';
            row.innerHTML = `
                <div class="student-search-info">
                    <p class="student-search-name">${escapeHtml(fullName)}</p>
                    <p class="student-search-meta">${escapeHtml(student.idNumber || '')} • ${escapeHtml(student.email || '')}</p>
                </div>
                <div class="student-search-action"></div>
            `;

            const actionWrap = row.querySelector('.student-search-action');
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'invite-student-btn';

            if (status === 'approved') {
                btn.textContent = 'Already member';
                btn.disabled = true;
            } else if (status === 'pending') {
                btn.textContent = 'Pending';
                btn.disabled = true;
            } else {
                btn.textContent = 'Invite student';
                btn.addEventListener('click', async () => {
                    try {
                        markActionButtonProcessing(btn, {
                            hasText: true,
                            processingText: 'Inviting...'
                        });
                        await postForm('invite_student_to_class.php', {
                            class_id: classId,
                            student_id: student.studentId
                        });
                        markActionButtonDone(btn, {
                            hasText: true,
                            doneText: 'Done'
                        });
                        showActionToast('Student invited successfully.');
                        await wait(450);
                        await loadMembers();
                        await loadSearchResults(searchInput.value);
                    } catch (error) {
                        restoreActionButton(btn);
                        showActionToast(error.message || 'Failed to invite student.', 'error');
                        alert(error.message || 'Failed to invite student.');
                    }
                });
            }

            actionWrap.appendChild(btn);
            searchResults.appendChild(row);
        });
    }

    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            loadSearchResults(searchInput.value).catch((error) => {
                searchResults.innerHTML = `<p class="members-empty">${escapeHtml(error.message || 'Failed to search students.')}</p>`;
            });
        }, 250);
    });

    loadClassHeader();
    loadMembers().catch((error) => {
        pendingList.innerHTML = `<p class="members-empty">${escapeHtml(error.message || 'Failed to load members.')}</p>`;
        approvedList.innerHTML = `<p class="members-empty">${escapeHtml(error.message || 'Failed to load members.')}</p>`;
    });
    searchResults.innerHTML = '<p class="members-empty">Type to search students.</p>';
});
