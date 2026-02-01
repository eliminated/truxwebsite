// Character counter
const postContent = document.getElementById('postContent');
const charCount = document.getElementById('charCount');

if (postContent) {
    postContent.addEventListener('input', function () {
        charCount.textContent = this.value.length;
        if (this.value.length >= 4500) {
            charCount.style.color = '#e74c3c';
        } else {
            charCount.style.color = '#999';
        }
    });
}

// Edit character counter
const editPostContent = document.getElementById('editPostContent');
const editCharCount = document.getElementById('editCharCount');

if (editPostContent) {
    editPostContent.addEventListener('input', function () {
        editCharCount.textContent = this.value.length;
        if (this.value.length >= 4500) {
            editCharCount.style.color = '#e74c3c';
        } else {
            editCharCount.style.color = '#999';
        }
    });
}

// Toggle functions
function toggleLinkInput() {
    const container = document.getElementById('linkInputContainer');
    container.style.display = container.style.display === 'none' ? 'block' : 'none';
}

function toggleCodeInput() {
    const container = document.getElementById('codeInputContainer');
    container.style.display = container.style.display === 'none' ? 'block' : 'none';
}

// File upload handler
let currentFile = null;
let currentFileType = null;

function handleFileUpload(input, type) {
    const file = input.files[0];
    if (file) {
        currentFile = file;
        currentFileType = type;

        const reader = new FileReader();
        reader.onload = function (e) {
            displayPreview(e.target.result, type, file.name);
        };
        reader.readAsDataURL(file);
    }
}

function displayPreview(src, type, fileName) {
    const previewArea = document.getElementById('previewArea');
    previewArea.classList.add('active');
    previewArea.innerHTML = '';

    const previewItem = document.createElement('div');
    previewItem.className = 'preview-item';

    if (type === 'image') {
        previewItem.innerHTML = `
            <img src="${src}" class="preview-image" alt="Preview">
            <button type="button" class="remove-preview" onclick="removePreview()">×</button>
        `;
    } else if (type === 'video') {
        previewItem.innerHTML = `
            <video src="${src}" class="preview-video" controls></video>
            <button type="button" class="remove-preview" onclick="removePreview()">×</button>
        `;
    }

    previewArea.appendChild(previewItem);
}

function removePreview() {
    const previewArea = document.getElementById('previewArea');
    previewArea.classList.remove('active');
    previewArea.innerHTML = '';
    document.getElementById('imageUpload').value = '';
    document.getElementById('videoUpload').value = '';
    currentFile = null;
    currentFileType = null;
}

// Post submission
const postForm = document.getElementById('postForm');
if (postForm) {
    postForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData();
        const content = document.getElementById('postContent').value;
        const linkInput = document.getElementById('linkInput').value;
        const codeSnippet = document.getElementById('codeSnippet').value;
        const codeLanguage = document.getElementById('codeLanguage').value;

        formData.append('content', content);

        if (currentFile) {
            if (currentFileType === 'image') {
                formData.append('image', currentFile);
            } else if (currentFileType === 'video') {
                formData.append('video', currentFile);
            }
        }

        if (linkInput) formData.append('link', linkInput);

        if (codeSnippet) {
            formData.append('codeSnippet', codeSnippet);
            formData.append('codeLanguage', codeLanguage);
        }

        const submitBtn = postForm.querySelector('.btn-submit');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Posting...';
        submitBtn.disabled = true;

        fetch('submit_post.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Post created successfully!', 'success');
                    postForm.reset();
                    document.getElementById('charCount').textContent = '0';
                    document.getElementById('previewArea').classList.remove('active');
                    document.getElementById('previewArea').innerHTML = '';
                    document.getElementById('linkInputContainer').style.display = 'none';
                    document.getElementById('codeInputContainer').style.display = 'none';
                    currentFile = null;
                    currentFileType = null;

                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showMessage('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('Network error. Please try again.', 'error');
                console.error('Error:', error);
            })
            .finally(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
    });
}

// DELETE POST
let deletePostId = null;

function deletePost(postId, event) {
    event.preventDefault();
    deletePostId = postId;
    document.getElementById('deleteModal').style.display = 'flex';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
    deletePostId = null;
}

function confirmDelete() {
    if (!deletePostId) return;

    fetch('post_actions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=delete&post_id=${deletePostId}`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelector(`[data-post-id="${deletePostId}"]`).remove();
                closeDeleteModal();
                showMessage('Post deleted successfully', 'success');
            } else {
                showMessage('Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            showMessage('Network error', 'error');
            console.error('Error:', error);
        });
}

// EDIT POST
function editPost(postId, event) {
    event.preventDefault();

    const postCard = document.querySelector(`[data-post-id="${postId}"]`);
    const content = postCard.querySelector('.post-content').getAttribute('data-content');

    document.getElementById('editPostId').value = postId;
    document.getElementById('editPostContent').value = content;
    document.getElementById('editCharCount').textContent = content.length;
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

const editPostForm = document.getElementById('editPostForm');
if (editPostForm) {
    editPostForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const postId = document.getElementById('editPostId').value;
        const content = document.getElementById('editPostContent').value;

        fetch('post_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=edit&post_id=${postId}&content=${encodeURIComponent(content)}`
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeEditModal();
                    showMessage('Post updated successfully', 'success');
                    setTimeout(() => window.location.reload(), 1000); // Reload to show edited badge
                } else {
                    showMessage('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('Network error', 'error');
                console.error('Error:', error);
            });
    });
}


// PIN/UNPIN POST
function pinPost(postId, event) {
    event.preventDefault();

    fetch('post_actions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=pin&post_id=${postId}`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('Post pinned', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showMessage('Error: ' + data.message, 'error');
            }
        });
}

function unpinPost(postId, event) {
    event.preventDefault();

    fetch('post_actions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=unpin&post_id=${postId}`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('Post unpinned', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showMessage('Error: ' + data.message, 'error');
            }
        });
}

// Continue in next part...
// SAVE/UNSAVE POST
function toggleSavePost(postId, button) {
    const isSaved = button.classList.contains('saved');
    const action = isSaved ? 'unsave' : 'save';

    fetch('post_actions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=${action}&post_id=${postId}`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (isSaved) {
                    button.classList.remove('saved');
                    button.innerHTML = '🔖 Save';
                } else {
                    button.classList.add('saved');
                    button.innerHTML = '🔖 Saved';
                }
                showMessage(data.message, 'success');
            } else {
                showMessage('Error: ' + data.message, 'error');
            }
        });
}

// REACTIONS SYSTEM
let reactionPopupTimeout;
let currentReactionButton = null;

function showReactionPopup(button) {
    clearTimeout(reactionPopupTimeout);
    currentReactionButton = button;

    const popup = document.getElementById('reactionPopup');
    const rect = button.getBoundingClientRect();

    popup.style.left = rect.left + 'px';
    popup.style.top = (rect.top - 60) + 'px';
    popup.style.display = 'flex';
}

function hideReactionPopup() {
    reactionPopupTimeout = setTimeout(() => {
        document.getElementById('reactionPopup').style.display = 'none';
        currentReactionButton = null;
    }, 300);
}

// Keep popup open when hovering over it
const reactionPopup = document.getElementById('reactionPopup');
if (reactionPopup) {
    reactionPopup.addEventListener('mouseenter', () => {
        clearTimeout(reactionPopupTimeout);
    });

    reactionPopup.addEventListener('mouseleave', () => {
        hideReactionPopup();
    });
}

// Handle reaction clicks
document.addEventListener('click', function (e) {
    if (e.target.closest('.reaction-option')) {
        const reactionType = e.target.closest('.reaction-option').getAttribute('data-reaction');
        const postId = currentReactionButton.getAttribute('data-post-id');

        submitReaction(postId, reactionType);
        document.getElementById('reactionPopup').style.display = 'none';
    }
});

function submitReaction(postId, reactionType) {
    fetch('reactions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `post_id=${postId}&reaction_type=${reactionType}`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload to update reaction counts
                window.location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

// COMMENTS SYSTEM
function openComments(postId) {
    document.getElementById('commentPostId').value = postId;
    document.getElementById('commentModal').style.display = 'flex';
    loadComments(postId);
}

function closeCommentModal() {
    document.getElementById('commentModal').style.display = 'none';
    document.getElementById('commentsContainer').innerHTML = '';
}

function loadComments(postId) {
    fetch(`comments.php?action=get&post_id=${postId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayComments(data.comments);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function displayComments(comments) {
    const container = document.getElementById('commentsContainer');

    if (comments.length === 0) {
        container.innerHTML = '<div class="no-comments">No comments yet. Be the first to comment!</div>';
        return;
    }

    container.innerHTML = '';

    // Separate parent comments and replies
    const parentComments = comments.filter(c => !c.parent_comment_id);
    const replies = comments.filter(c => c.parent_comment_id);

    parentComments.forEach(comment => {
        const commentEl = createCommentElement(comment);
        container.appendChild(commentEl);

        // Add replies
        const commentReplies = replies.filter(r => r.parent_comment_id == comment.comment_id);
        if (commentReplies.length > 0) {
            const repliesContainer = document.createElement('div');
            repliesContainer.className = 'comment-replies';
            commentReplies.forEach(reply => {
                repliesContainer.appendChild(createCommentElement(reply, true));
            });
            container.appendChild(repliesContainer);
        }
    });
}

function createCommentElement(comment, isReply = false) {
    const div = document.createElement('div');
    div.className = 'comment-item' + (isReply ? ' reply' : '');
    div.setAttribute('data-comment-id', comment.comment_id);

    let actionsHTML = '';
    if (comment.is_own) {
        actionsHTML = `<button class="delete-comment-btn" onclick="deleteComment(${comment.comment_id})">Delete</button>`;
    }
    if (!isReply) {
        actionsHTML += `<button class="reply-comment-btn" onclick="replyToComment(${comment.comment_id}, '${comment.username}')">Reply</button>`;
    }

    let avatarHTML;
    if (comment.profile_picture) {
        avatarHTML = `<div class="comment-avatar" style="background-image: url('${comment.profile_picture}'); background-size: cover; background-position: center;"></div>`;
    } else {
        avatarHTML = `<div class="comment-avatar">${comment.first_letter}</div>`;
    }

    div.innerHTML = `
        ${avatarHTML}
        <div class="comment-content">
            <div class="comment-header">
                <span class="comment-author">${escapeHtml(comment.username)}</span>
                <span class="comment-time">${comment.time_ago}</span>
            </div>
            <div class="comment-text">${escapeHtml(comment.comment_text)}</div>
            <div class="comment-actions">${actionsHTML}</div>
        </div>
    `;

    return div;
}

// Add comment form submission
const addCommentForm = document.getElementById('addCommentForm');
if (addCommentForm) {
    addCommentForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const postId = document.getElementById('commentPostId').value;
        const commentText = document.getElementById('commentText').value;
        const parentId = document.getElementById('commentParentId').value;

        if (!commentText.trim()) {
            showMessage('Comment cannot be empty', 'error');
            return;
        }

        const formData = new URLSearchParams();
        formData.append('action', 'add');
        formData.append('post_id', postId);
        formData.append('comment_text', commentText);
        if (parentId) formData.append('parent_comment_id', parentId);

        fetch('comments.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('commentText').value = '';
                    document.getElementById('commentParentId').value = '';
                    loadComments(postId);
                    showMessage('Comment added', 'success');
                } else {
                    showMessage('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('Network error', 'error');
                console.error('Error:', error);
            });
    });
}

function replyToComment(commentId, username) {
    document.getElementById('commentParentId').value = commentId;
    document.getElementById('commentText').placeholder = `Replying to ${username}...`;
    document.getElementById('commentText').focus();
}

function deleteComment(commentId) {
    if (!confirm('Delete this comment?')) return;

    const postId = document.getElementById('commentPostId').value;

    fetch('comments.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=delete&comment_id=${commentId}`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadComments(postId);
                showMessage('Comment deleted', 'success');
            } else {
                showMessage('Error: ' + data.message, 'error');
            }
        });
}

// SHARE POST
function sharePost(postId) {
    const url = window.location.origin + window.location.pathname + '?post=' + postId;

    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
            showMessage('Link copied to clipboard!', 'success');
        }).catch(() => {
            promptCopyFallback(url);
        });
    } else {
        promptCopyFallback(url);
    }
}

function promptCopyFallback(url) {
    const input = document.createElement('input');
    input.value = url;
    document.body.appendChild(input);
    input.select();
    document.execCommand('copy');
    document.body.removeChild(input);
    showMessage('Link copied!', 'success');
}

// POST MENU TOGGLE
function togglePostMenu(postId) {
    const menu = document.getElementById('menu-' + postId);
    const isVisible = menu.style.display === 'block';

    // Close all menus first
    document.querySelectorAll('.post-dropdown').forEach(m => m.style.display = 'none');

    if (!isVisible) {
        menu.style.display = 'block';
    }
}

// Close menus when clicking outside
document.addEventListener('click', function (e) {
    if (!e.target.closest('.post-menu')) {
        document.querySelectorAll('.post-dropdown').forEach(m => m.style.display = 'none');
    }
});

// CUSTOM CONTEXT MENU
document.addEventListener('contextmenu', function (e) {
    const postCard = e.target.closest('.post-card');
    const postImage = e.target.closest('.post-media img');
    const postAuthor = e.target.closest('.post-author');

    if (postCard || postImage || postAuthor) {
        e.preventDefault();
        showCustomContextMenu(e, postCard, postImage, postAuthor);
    }
});

function showCustomContextMenu(e, postCard, postImage, postAuthor) {
    const menu = document.getElementById('customContextMenu');
    const menuItems = document.getElementById('contextMenuItems');
    menuItems.innerHTML = '';

    // Post context menu
    if (postCard && !postImage && !postAuthor) {
        const postId = postCard.getAttribute('data-post-id');
        const userId = postCard.getAttribute('data-user-id');
        const isOwnPost = (userId == currentUserId);

        menuItems.innerHTML = `
            <li onclick="sharePost(${postId})">📤 Share Post</li>
            <li onclick="copyPostLink(${postId})">🔗 Copy Link</li>
            ${isOwnPost ? `
                <li onclick="editPost(${postId}, event)">✏️ Edit Post</li>
                <li onclick="deletePost(${postId}, event)" class="danger">🗑️ Delete Post</li>
            ` : `
                <li onclick="reportPost(${postId})">⚠️ Report Post</li>
            `}
        `;
    }

    // Image context menu
    else if (postImage) {
        const imgSrc = postImage.src;
        menuItems.innerHTML = `
            <li onclick="openImageLightbox('${imgSrc}')">🔍 View Full Size</li>
            <li onclick="downloadImage('${imgSrc}')">💾 Download Image</li>
            <li onclick="copyImageUrl('${imgSrc}')">🔗 Copy Image URL</li>
            <li onclick="window.open('${imgSrc}', '_blank')">🔗 Open in New Tab</li>
        `;
    }

    // Username context menu
    else if (postAuthor) {
        const username = postAuthor.getAttribute('data-username');
        menuItems.innerHTML = `
            <li onclick="viewUserProfile('${username}')">👤 View Profile</li>
            <li onclick="sendMessage('${username}')">💬 Send Message</li>
            <li onclick="followUser('${username}')">➕ Follow</li>
        `;
    }

    // Position menu
    menu.style.left = e.pageX + 'px';
    menu.style.top = e.pageY + 'px';
    menu.style.display = 'block';
}

// Close context menu when clicking elsewhere
document.addEventListener('click', function () {
    document.getElementById('customContextMenu').style.display = 'none';
});

function copyPostLink(postId) {
    const url = window.location.origin + window.location.pathname + '?post=' + postId;
    navigator.clipboard.writeText(url);
    showMessage('Link copied!', 'success');
}

function reportPost(postId) {
    showMessage('Report functionality coming soon', 'success');
}

function downloadImage(imgSrc) {
    const link = document.createElement('a');
    link.href = imgSrc;
    link.download = imgSrc.split('/').pop();
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    showMessage('Image download started', 'success');
}

function copyImageUrl(imgSrc) {
    navigator.clipboard.writeText(imgSrc);
    showMessage('Image URL copied!', 'success');
}

function viewUserProfile(username) {
    window.location.href = 'profile.php?user=' + username;
}

function sendMessage(username) {
    window.location.href = 'chat.php?user=' + username;
}

function followUser(username) {
    showMessage('Follow functionality coming in Phase 4', 'success');
}

// IMAGE LIGHTBOX
function openImageLightbox(src) {
    document.getElementById('lightboxImage').src = src;
    document.getElementById('imageLightbox').style.display = 'flex';
}

function closeLightbox() {
    document.getElementById('imageLightbox').style.display = 'none';
}

// Escape HTML helper
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// Show message
function showMessage(message, type) {
    const messageDiv = document.getElementById('postMessage');
    if (messageDiv) {
        messageDiv.textContent = message;
        messageDiv.className = 'post-message ' + type;
        messageDiv.style.display = 'block';

        setTimeout(() => {
            messageDiv.style.display = 'none';
        }, 5000);
    } else {
        // Fallback for pages without postMessage div
        alert(message);
    }
}

// Close modals with Escape key
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        closeDeleteModal();
        closeEditModal();
        closeCommentModal();
        closeLightbox();
    }
});

// EDIT HISTORY
// TEMPORARTY REMOVED

//Closed EDIT HISTORY MODAL TEMPORARY REMOVED

// Display Edit history function is also temporary removed

// Temoirary removed for a while

// Update the edit post success callback to reload page
// In the editPostForm submit handler, change:
