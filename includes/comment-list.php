<?php
// Get comments from global scope if not passed directly
$comments = isset($comments) ? $comments : (isset($GLOBALS['comments']) ? $GLOBALS['comments'] : []);

/**
 * Comment list template
 * 
 * @param array $comments Array of comments (can be nested)
 * @param int $level Current nesting level (used for indentation)
 */
function displayComments($comments, $level = 0) {
    global $pdo;
    
    foreach ($comments as $comment) {
        $isAdmin = !empty($comment['is_admin']);
        $hasReplies = !empty($comment['replies']);
        $isReply = $level > 0;
        
        // Calculate margin based on nesting level (max 3 levels deep)
        $marginClass = $level === 0 ? 'ml-0' : ($level === 1 ? 'ml-4 md:ml-8' : 'ml-8 md:ml-16');
        $borderColor = $level % 2 === 0 ? 'border-l-primary' : 'border-l-secondary';
        ?>
        <div class="comment-item <?php echo $marginClass; ?> border-l-2 <?php echo $borderColor; ?> pl-4 py-4" 
             id="comment-<?php echo $comment['id']; ?>">
            
            <div class="flex items-start">
                <!-- User Avatar -->
                <div class="flex-shrink-0 mr-3">
                    <img src="<?php echo !empty($comment['profile_image']) ? '/uploads/profiles/' . htmlspecialchars($comment['profile_image']) : '/assets/images/default-avatar.png'; ?>" 
                         alt="<?php echo htmlspecialchars($comment['username']); ?>"
                         class="w-10 h-10 rounded-full object-cover border-2 <?php echo $isAdmin ? 'border-primary' : 'border-gray-200'; ?>">
                </div>
                
                <!-- Comment Content -->
                <div class="flex-1 min-w-0">
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <!-- Comment Header -->
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center space-x-2">
                                <h4 class="font-medium text-gray-900">
                                    <?php echo htmlspecialchars($comment['username']); ?>
                                    <?php if ($isAdmin): ?>
                                        <span class="ml-1 px-2 py-0.5 bg-primary/10 text-primary text-xs rounded-full">Admin</span>
                                    <?php endif; ?>
                                </h4>
                                <span class="text-xs text-gray-500">•</span>
                                <span class="text-xs text-gray-500" title="<?php echo date('M j, Y \a\t g:i a', strtotime($comment['created_at'])); ?>">
                                    <?php echo $comment['created_ago']; ?>
                                </span>
                            </div>
                            
                            <?php if (isLoggedIn()): ?>
                            <div class="relative">
                                <button class="text-gray-400 hover:text-gray-600 focus:outline-none" 
                                        onclick="toggleCommentMenu(<?php echo $comment['id']; ?>)">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                                
                                <!-- Comment Dropdown Menu -->
                                <div id="comment-menu-<?php echo $comment['id']; ?>" 
                                     class="hidden absolute right-0 mt-1 w-40 bg-white rounded-md shadow-lg py-1 z-10 border border-gray-200">
                                    <?php if (isLoggedIn() && ($_SESSION['user_id'] == $comment['user_id'] || isAdmin())): ?>
                                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                           onclick="editComment(<?php echo $comment['id']; ?>, '<?php echo addslashes($comment['content']); ?>'); return false;">
                                            <i class="far fa-edit mr-2"></i> Edit
                                        </a>
                                        <a href="#" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100"
                                           onclick="deleteComment(<?php echo $comment['id']; ?>); return false;">
                                            <i class="far fa-trash-alt mr-2"></i> Delete
                                        </a>
                                    <?php endif; ?>
                                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                       onclick="reportComment(<?php echo $comment['id']; ?>); return false;">
                                        <i class="far fa-flag mr-2"></i> Report
                                    </a>
                                    <a href="#" class="block px-4 py-2 text-sm text-primary hover:bg-gray-100"
                                       onclick="replyToComment(<?php echo $comment['id']; ?>, '<?php echo addslashes($comment['username']); ?>'); return false;">
                                        <i class="fas fa-reply mr-2"></i> Reply
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Comment Body -->
                        <div class="prose prose-sm max-w-none text-gray-700 mb-3" id="comment-content-<?php echo $comment['id']; ?>">
                            <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                        </div>
                        
                        <!-- Comment Actions -->
                        <div class="flex items-center text-xs text-gray-500 space-x-4">
                            <button class="flex items-center hover:text-primary transition-colors"
                                    onclick="likeComment(<?php echo $comment['id']; ?>)">
                                <i class="far fa-thumbs-up mr-1"></i>
                                <span id="comment-like-count-<?php echo $comment['id']; ?>">0</span>
                            </button>
                            <?php if (isLoggedIn()): ?>
                                <button class="hover:text-primary transition-colors"
                                        onclick="replyToComment(<?php echo $comment['id']; ?>, '<?php echo addslashes($comment['username']); ?>')">
                                    <i class="fas fa-reply mr-1"></i> Reply
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Reply Form (Hidden by default) -->
                    <div id="reply-form-<?php echo $comment['id']; ?>" class="mt-3 hidden">
                        <form class="comment-reply-form" data-parent-id="<?php echo $comment['id']; ?>">
                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                            <input type="hidden" name="parent_id" value="<?php echo $comment['id']; ?>">
                            <div class="flex">
                                <div class="flex-grow">
                                    <textarea name="content" rows="2" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-primary focus:border-transparent" 
                                              placeholder="Write your reply..." required></textarea>
                                </div>
                                <button type="submit" class="px-4 bg-primary text-white rounded-r-lg hover:bg-primary-dark transition-colors">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                            <div class="mt-1 flex justify-end">
                                <button type="button" class="text-xs text-gray-500 hover:text-gray-700" 
                                        onclick="document.getElementById('reply-form-<?php echo $comment['id']; ?>').classList.add('hidden')">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Nested Comments -->
            <?php if ($hasReplies): ?>
                <div class="mt-4 space-y-4">
                    <?php displayComments($comment['replies'], $level + 1); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}

// Start rendering comments
displayComments($comments);
?>

<!-- Edit Comment Modal -->
<div id="edit-comment-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 max-w-lg w-full mx-4">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Edit Comment</h3>
        <form id="edit-comment-form">
            <input type="hidden" id="edit-comment-id" name="comment_id">
            <div class="mb-4">
                <textarea id="edit-comment-content" name="content" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" required></textarea>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('edit-comment-modal').classList.add('hidden')" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Toggle comment dropdown menu
function toggleCommentMenu(commentId) {
    const menu = document.getElementById(`comment-menu-${commentId}`);
    document.querySelectorAll('.comment-dropdown-menu').forEach(m => {
        if (m.id !== `comment-menu-${commentId}`) m.classList.add('hidden');
    });
    menu.classList.toggle('hidden');
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.relative')) {
        document.querySelectorAll('.comment-dropdown-menu').forEach(menu => {
            menu.classList.add('hidden');
        });
    }
});

// Show reply form
function replyToComment(commentId, username) {
    const replyForm = document.getElementById(`reply-form-${commentId}`);
    const textarea = replyForm.querySelector('textarea');
    
    // Hide all other reply forms
    document.querySelectorAll('[id^="reply-form-"]').forEach(form => {
        if (form.id !== `reply-form-${commentId}`) {
            form.classList.add('hidden');
        }
    });
    
    // Toggle the selected reply form
    replyForm.classList.toggle('hidden');
    
    // Focus the textarea if showing the form
    if (!replyForm.classList.contains('hidden')) {
        textarea.value = `@${username} `;
        textarea.focus();
    }
}

// Handle reply form submission
document.querySelectorAll('.comment-reply-form').forEach(form => {
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        if (!<?php echo isLoggedIn() ? 'true' : 'false'; ?>) {
            showLoginAlert();
            return;
        }
        
        const formData = new FormData(this);
        const submitButton = this.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.innerHTML;
        
        try {
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            const response = await fetch('/api/blog_interactions.php?action=comment', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Reload comments
                await loadComments();
                
                // Update comment count
                const commentCount = document.getElementById('comment-count');
                const currentCount = parseInt(commentCount.textContent.match(/\d+/)[0]);
                commentCount.textContent = `(${currentCount + 1})`;
                
                // Hide the reply form
                this.closest('.hidden').classList.add('hidden');
            } else {
                alert(data.message || 'Failed to post reply. Please try again.');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonText;
        }
    });
});

// Edit comment
function editComment(commentId, currentContent) {
    document.getElementById('edit-comment-id').value = commentId;
    document.getElementById('edit-comment-content').value = currentContent;
    document.getElementById('edit-comment-modal').classList.remove('hidden');
    
    // Close any open dropdown menus
    document.querySelectorAll('.comment-dropdown-menu').forEach(menu => {
        menu.classList.add('hidden');
    });
}

// Handle edit comment form submission
document.getElementById('edit-comment-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitButton = this.querySelector('button[type="submit"]');
    const originalButtonText = submitButton.textContent;
    
    try {
        submitButton.disabled = true;
        submitButton.textContent = 'Saving...';
        
        const response = await fetch('/api/blog_interactions.php?action=edit_comment', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Update the comment content
            const commentContent = document.getElementById(`comment-content-${formData.get('comment_id')}`);
            if (commentContent) {
                commentContent.innerHTML = formData.get('content').replace(/\n/g, '<br>');
            }
            
            // Close the modal
            document.getElementById('edit-comment-modal').classList.add('hidden');
        } else {
            alert(data.message || 'Failed to update comment. Please try again.');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    } finally {
        submitButton.disabled = false;
        submitButton.textContent = originalButtonText;
    }
});

// Delete comment
async function deleteComment(commentId) {
    if (!confirm('Are you sure you want to delete this comment? This action cannot be undone.')) {
        return;
    }
    
    try {
        const response = await fetch('/api/blog_interactions.php?action=delete_comment', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ comment_id: commentId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Remove the comment from the DOM
            const commentElement = document.getElementById(`comment-${commentId}`);
            if (commentElement) {
                commentElement.remove();
            }
            
            // Update comment count
            const commentCount = document.getElementById('comment-count');
            const currentCount = parseInt(commentCount.textContent.match(/\d+/)[0]);
            commentCount.textContent = `(${Math.max(0, currentCount - 1)})`;
            
            // If there are no more comments, show the "no comments" message
            if (document.querySelectorAll('.comment-item').length === 0) {
                document.getElementById('comments-list').innerHTML = 
                    '<p class="text-gray-500 text-center py-8">No comments yet. Be the first to comment!</p>';
            }
        } else {
            alert(data.message || 'Failed to delete comment. Please try again.');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    }
}

// Report comment
function reportComment(commentId) {
    const reason = prompt('Please enter the reason for reporting this comment:');
    if (reason === null || reason.trim() === '') return;
    
    // In a real application, you would send this to your server
    fetch('/api/blog_interactions.php?action=report_comment', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            comment_id: commentId,
            reason: reason
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Thank you for reporting this comment. Our team will review it shortly.');
        } else {
            alert('Failed to report comment. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}

// Like comment
async function likeComment(commentId) {
    if (!<?php echo isLoggedIn() ? 'true' : 'false'; ?>) {
        showLoginAlert();
        return;
    }
    
    try {
        const response = await fetch('/api/blog_interactions.php?action=like_comment', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ comment_id: commentId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Update the like count
            const likeCountElement = document.getElementById(`comment-like-count-${commentId}`);
            if (likeCountElement) {
                likeCountElement.textContent = data.like_count;
            }
        } else {
            alert(data.message || 'Failed to like comment. Please try again.');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    }
}
</script>
