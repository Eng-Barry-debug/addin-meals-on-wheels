/**
 * Blog Interactions JavaScript
 * Handles likes, comments, and social sharing for blog posts
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeBlogInteractions();
    
    // Load comments when the page loads
    if (document.getElementById('comments-list')) {
        loadComments();
    }
});

/**
 * Initialize all blog interaction event listeners
 */
function initializeBlogInteractions() {
    // Check if user is logged in
    function isUserLoggedIn() {
        // Check for the meta tag first
        const loggedInMeta = document.querySelector('meta[name="user-logged-in"]');
        if (loggedInMeta && loggedInMeta.content === '1') {
            return true;
        }
        
        // Check for session variable via PHP
        if (typeof window.isLoggedIn === 'function' && window.isLoggedIn()) {
            return true;
        }
        
        // Check for session cookie as last resort
        const cookies = document.cookie.split(';').map(c => c.trim());
        const sessionCookie = cookies.find(c => c.startsWith('PHPSESSID=') || c.startsWith('ci_session='));
        
        if (sessionCookie) {
            // If we have a session cookie, we'll assume the user is logged in
            // This is a fallback and might need adjustment based on your auth system
            return true;
        }
        
        return false;
    }

    // Show login alert and redirect to login page
    function showLoginAlert() {
        if (confirm('You need to be logged in to perform this action. Would you like to log in now?')) {
            const currentUrl = window.location.pathname + window.location.search;
            window.location.href = '/auth/login.php?redirect=' + encodeURIComponent(currentUrl);
        }
    }

    // Handle like button click
    const likeButton = document.getElementById('like-button');
    if (likeButton) {
        likeButton.addEventListener('click', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (!isUserLoggedIn()) {
                showLoginAlert();
                return false;
            }
            
            // If we get here, user is logged in, so handle the like
            await handleLike(e);
            return false;
        });
    }

    const commentForm = document.getElementById('comment-form');
    if (commentForm) {
        commentForm.addEventListener('submit', function(e) {
            if (!isUserLoggedIn()) {
                e.preventDefault();
                showLoginAlert();
                return false;
            }
            handleCommentSubmit(e);
        });
    }

    // Handle comment like buttons
    document.addEventListener('click', function(e) {
        if (e.target.closest('.like-comment-btn')) {
            e.preventDefault();
            const button = e.target.closest('.like-comment-btn');
            const commentId = button.dataset.commentId;
            likeComment(commentId, button);
        }
    });

    // Handle reply buttons
    document.addEventListener('click', function(e) {
        if (e.target.closest('.reply-comment-btn')) {
            e.preventDefault();
            const button = e.target.closest('.reply-comment-btn');
            const commentId = button.dataset.commentId;
            const username = button.dataset.username;
            showReplyForm(commentId, username);
        }
    });

    // Initialize social sharing buttons
    initializeSocialSharing();

    // Initialize reading progress bar
    initializeReadingProgress();
}

/**
 * Generic API fetch wrapper with error handling
 */
async function apiFetch(url, options = {}) {
    try {
        const defaultOptions = {
            headers: {
                'Accept': 'application/json',
                ...options.headers
            },
            ...options
        };

        const response = await fetch(url, defaultOptions);
        
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.warn('Non-JSON response received:', text.substring(0, 500));
            throw new Error(`Server returned non-JSON response (${response.status})`);
        }
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        return data;
        
    } catch (error) {
        console.error('API fetch error:', error);
        throw error;
    }
}

/**
 * Handle post like/unlike
 */
async function handleLike(e) {
    e.preventDefault();
    
    const button = e.currentTarget;
    const postId = button.getAttribute('data-post-id');
    const isLiked = button.classList.contains('text-red-600');
    
    // Find the like count element - check multiple possible locations
    let likeCount = button.querySelector('.like-count');
    
    // If not found, check within the button's children (for the logged-in state)
    if (!likeCount) {
        likeCount = button.querySelector('span.like-count');
    }
    
    // If still not found, check parent element (for the non-logged-in state)
    if (!likeCount && button.parentElement) {
        likeCount = button.parentElement.querySelector('.like-count');
    }
    
    // If still not found, look for a sibling element with the like-count class
    if (!likeCount && button.nextElementSibling) {
        likeCount = button.nextElementSibling.classList?.contains('like-count') 
            ? button.nextElementSibling 
            : null;
    }
    
    // If still not found, look for any element with the like-count class in the document
    if (!likeCount) {
        likeCount = document.querySelector('.like-count');
    }
    
    // If we still can't find it, log a warning but don't return
    if (!likeCount) {
        console.warn('Like count element not found, will update button text only');
    }

    // Disable button during request
    button.disabled = true;

    try {
        const formData = new FormData();
        formData.append('action', 'like');
        formData.append('post_id', postId);
        formData.append('like', isLiked ? '0' : '1');

        const response = await fetch('/api/blog_interactions.php?action=like', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.redirect) {
            window.location.href = data.redirect;
            return;
        }

        if (data.success) {
            // Update like count and button state
            if (likeCount) {
                likeCount.textContent = data.like_count || 0;
            }

            if (data.liked) {
                button.classList.add('bg-red-100', 'text-red-600');
                button.classList.remove('bg-gray-100');
                button.innerHTML = '<i class="fas fa-heart"></i> ' + (likeCount ? likeCount.textContent : '0');
            } else {
                button.classList.remove('bg-red-100', 'text-red-600');
                button.classList.add('bg-gray-100');
                button.innerHTML = '<i class="far fa-heart"></i> ' + (likeCount ? likeCount.textContent : '0');
            }

            showNotification(data.message || (data.liked ? 'Post liked!' : 'Like removed'), 'success');
        } else {
            throw new Error(data.message || 'Failed to update like');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification(error.message || 'An error occurred. Please try again.', 'error');
    } finally {
        button.disabled = false;
    }
}

/**
 * Handle comment submission
 */
async function handleCommentSubmit(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    formData.append('action', 'comment');
    
    const submitButton = form.querySelector('button[type="submit"]');
    const originalButtonText = submitButton.innerHTML;
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Posting...';
    
    try {
        const response = await fetch('/api/blog_interactions.php?action=comment', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.redirect) {
            window.location.href = data.redirect;
            return;
        }
        
        if (data.success) {
            // Clear the form
            form.reset();
            
            // Show success message
            showNotification('Comment posted successfully!', 'success');
            
            // Instead of reloading all comments, just append the new one
            if (data.comment_html) {
                const commentsList = document.getElementById('comments-list');
                if (commentsList) {
                    // Remove the "no comments" message if it exists
                    const noCommentsMsg = commentsList.querySelector('.no-comments-message');
                    if (noCommentsMsg) {
                        commentsList.innerHTML = '';
                    }
                    
                    // Add the new comment at the top
                    commentsList.insertAdjacentHTML('afterbegin', data.comment_html);
                    
                    // Highlight the new comment
                    if (data.comment_id) {
                        highlightNewComment(data.comment_id);
                    }
                }
            }
            
            // Update comment count
            updateCommentCount(1);
        } else {
            throw new Error(data.message || 'Failed to post comment');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification(error.message || 'An error occurred. Please try again.', 'error');
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = originalButtonText;
    }
}

/**
 * Load comments for the current post
 */
async function loadComments(postId) {
    const commentsList = document.getElementById('comments-list');
    console.log('loadComments called with postId:', postId);

    if (!commentsList) {
        console.error('Comments list element not found');
        return;
    }

    // If postId is not provided, try to get it from the URL or form
    if (!postId) {
        const urlParams = new URLSearchParams(window.location.search);
        postId = urlParams.get('id');
        console.log('Extracted postId from URL:', postId);

        if (!postId) {
            const postIdInput = document.querySelector('input[name="post_id"]');
            if (postIdInput) {
                postId = postIdInput.value;
                console.log('Extracted postId from form input:', postId);
            }
        }
    }

    if (!postId) {
        const errorMsg = 'No post ID found for loading comments';
        console.error(errorMsg);
        commentsList.innerHTML = `<div class="text-center py-4 text-red-600">${errorMsg}</div>`;
        return;
    }

    try {
        // Show loading state
        const originalContent = commentsList.innerHTML;
        commentsList.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin text-xl text-primary"></i><p class="mt-2 text-gray-600">Loading comments...</p></div>';
        
        console.log('Fetching comments for post ID:', postId);
        const response = await fetch(`/api/blog_interactions.php?action=get_comments&post_id=${postId}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Received comments data:', data);

        if (data.success && data.html) {
            commentsList.innerHTML = data.html;
            console.log('Comments loaded successfully');
        } else {
            throw new Error(data.message || 'Failed to load comments: Invalid response format');
        }
    } catch (error) {
        console.error('Error loading comments:', error);
        const errorMessage = error.message || 'An error occurred while loading comments';
        commentsList.innerHTML = `
            <div class="text-center py-4 text-red-600">
                <i class="fas fa-exclamation-triangle"></i>
                <p class="mt-2">${errorMessage}</p>
                <p class="text-sm text-gray-500 mt-2">Please refresh the page or try again later.</p>
            </div>
        `;
    }
}

/**
 * Like a comment
 */
async function likeComment(commentId, button) {
    if (!commentId || !button) return;
    
    // Show loading state
    const originalHTML = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    button.disabled = true;
    
    try {
        const formData = new FormData();
        formData.append('action', 'like_comment');
        formData.append('comment_id', commentId);
        
        const response = await fetch('/api/blog_interactions.php?action=like_comment', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.redirect) {
            window.location.href = data.redirect;
            return;
        }
        
        if (data.success) {
            // Update like count
            const likeCount = button.querySelector('.like-count');
            if (likeCount) {
                likeCount.textContent = data.like_count;
            }
            
            // Update button appearance
            if (data.liked) {
                button.classList.add('text-red-600');
                button.classList.remove('text-gray-500');
            } else {
                button.classList.remove('text-red-600');
                button.classList.add('text-gray-500');
            }
        } else {
            throw new Error(data.message || 'Failed to like comment');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification(error.message || 'An error occurred. Please try again.', 'error');
    } finally {
        button.disabled = false;
        button.innerHTML = originalHTML;
    }
}

/**
 * Show reply form for a comment
 */
function showReplyForm(commentId, username) {
    // Hide any other reply forms
    document.querySelectorAll('.reply-form-container').forEach(el => {
        el.classList.add('hidden');
    });
    
    // Remove existing reply form if it exists
    const existingForm = document.getElementById(`reply-form-${commentId}`);
    if (existingForm) {
        existingForm.classList.toggle('hidden');
        if (!existingForm.classList.contains('hidden')) {
            existingForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            existingForm.querySelector('textarea').focus();
        }
        return;
    }
    
    // Create new reply form
    const replyForm = document.createElement('div');
    replyForm.id = `reply-form-${commentId}`;
    replyForm.className = 'reply-form-container mt-4 pl-6 border-l-2 border-gray-200';
    
    replyForm.innerHTML = `
        <form class="comment-reply-form" data-parent-id="${commentId}">
            <input type="hidden" name="post_id" value="${document.querySelector('input[name="post_id"]').value}">
            <input type="hidden" name="parent_id" value="${commentId}">
            <div class="mb-2">
                <textarea name="content" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" 
                          placeholder="Reply to ${username}..." required></textarea>
            </div>
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="this.closest('.reply-form-container').classList.add('hidden')" 
                        class="px-3 py-1 text-sm text-gray-600 hover:text-gray-800 transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-1 bg-primary text-white text-sm rounded-lg hover:bg-primary-dark transition-colors">
                    Reply
                </button>
            </div>
        </form>
    `;
    
    // Add event listener to the form
    replyForm.querySelector('form').addEventListener('submit', handleCommentSubmit);
    
    // Insert after the comment
    const commentElement = document.getElementById(`comment-${commentId}`);
    if (commentElement) {
        commentElement.insertAdjacentElement('afterend', replyForm);
        
        // Scroll to and focus the form
        setTimeout(() => {
            replyForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            replyForm.querySelector('textarea').focus();
        }, 100);
    }
}

/**
 * Update comment count display
 */
function updateCommentCount(change = 0) {
    const commentCount = document.getElementById('comment-count');
    const commentCountDisplay = document.getElementById('comment-count-display');
    
    if (commentCount) {
        const currentText = commentCount.textContent;
        const currentCount = parseInt(currentText.match(/\d+/)) || 0;
        const newCount = currentCount + change;
        commentCount.textContent = `(${newCount})`;
    }
    
    if (commentCountDisplay) {
        const currentText = commentCountDisplay.textContent;
        const currentCount = parseInt(currentText.match(/\d+/)) || 0;
        const newCount = currentCount + change;
        commentCountDisplay.textContent = `${newCount} comment${newCount !== 1 ? 's' : ''}`;
    }
}

/**
 * Highlight a new comment
 */
function highlightNewComment(commentId) {
    const newComment = document.getElementById(`comment-${commentId}`);
    if (newComment) {
        newComment.scrollIntoView({ behavior: 'smooth', block: 'center' });
        newComment.classList.add('bg-yellow-50', 'transition-colors', 'duration-1000');
        setTimeout(() => {
            newComment.classList.remove('bg-yellow-50');
        }, 3000);
    }
}

/**
 * Show login alert
 */
function showLoginAlert() {
    if (confirm('Please login to perform this action. Would you like to go to the login page?')) {
        const currentUrl = window.location.pathname + window.location.search;
        window.location.href = '/auth/login.php?redirect=' + encodeURIComponent(currentUrl);
    }
}

/**
 * Initialize social sharing buttons
 */
function initializeSocialSharing() {
    // Twitter share
    const twitterBtn = document.getElementById('share-twitter');
    if (twitterBtn) {
        twitterBtn.addEventListener('click', shareOnTwitter);
    }
    
    // Facebook share
    const facebookBtn = document.getElementById('share-facebook');
    if (facebookBtn) {
        facebookBtn.addEventListener('click', shareOnFacebook);
    }
    
    // LinkedIn share
    const linkedinBtn = document.getElementById('share-linkedin');
    if (linkedinBtn) {
        linkedinBtn.addEventListener('click', shareOnLinkedIn);
    }
    
    // Copy link
    const copyLinkBtn = document.getElementById('copy-link');
    if (copyLinkBtn) {
        copyLinkBtn.addEventListener('click', copyPostLink);
    }
}

/**
 * Social sharing functions
 */
function shareOnTwitter() {
    const url = encodeURIComponent(window.location.href);
    const text = encodeURIComponent(document.title);
    window.open(`https://twitter.com/intent/tweet?url=${url}&text=${text}`, '_blank', 'width=600,height=400');
}

function shareOnFacebook() {
    const url = encodeURIComponent(window.location.href);
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank', 'width=600,height=400');
}

function shareOnLinkedIn() {
    const url = encodeURIComponent(window.location.href);
    window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${url}`, '_blank', 'width=600,height=400');
}

function copyPostLink() {
    const url = window.location.href;
    navigator.clipboard.writeText(url).then(() => {
        showNotification('Link copied to clipboard!', 'success');
    }).catch(() => {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = url;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showNotification('Link copied to clipboard!', 'success');
    });
}

/**
 * Initialize reading progress bar
 */
function initializeReadingProgress() {
    const progressBar = document.getElementById('progress-fill');
    if (!progressBar) return;
    
    window.addEventListener('scroll', updateReadingProgress);
    updateReadingProgress(); // Initial update
}

/**
 * Update reading progress bar
 */
function updateReadingProgress() {
    const progressBar = document.getElementById('progress-fill');
    if (!progressBar) return;
    
    const windowHeight = window.innerHeight;
    const documentHeight = document.documentElement.scrollHeight - windowHeight;
    const scrolled = window.scrollY;
    const progress = (scrolled / documentHeight) * 100;
    
    progressBar.style.width = Math.min(100, Math.max(0, progress)) + '%';
}

/**
 * Show notification message
 */
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotification = document.querySelector('.global-notification');
    if (existingNotification) {
        existingNotification.remove();
    }
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `global-notification fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full ${getNotificationClasses(type)}`;
    notification.innerHTML = `
        <div class="flex items-center space-x-2">
            <i class="${getNotificationIcon(type)}"></i>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 300);
    }, 5000);
    
    // Click to dismiss
    notification.addEventListener('click', () => {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 300);
    });
}

/**
 * Get CSS classes for notification type
 */
function getNotificationClasses(type) {
    const classes = {
        success: 'bg-green-500 text-white',
        error: 'bg-red-500 text-white',
        warning: 'bg-yellow-500 text-white',
        info: 'bg-blue-500 text-white'
    };
    return classes[type] || classes.info;
}

/**
 * Get icon for notification type
 */
function getNotificationIcon(type) {
    const icons = {
        success: 'fas fa-check-circle',
        error: 'fas fa-exclamation-circle',
        warning: 'fas fa-exclamation-triangle',
        info: 'fas fa-info-circle'
    };
    return icons[type] || icons.info;
}

/**
 * Utility function to debounce rapid function calls
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Export functions for global access (if using modules)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        initializeBlogInteractions,
        handleLike,
        handleCommentSubmit,
        loadComments,
        likeComment,
        showReplyForm,
        shareOnTwitter,
        shareOnFacebook,
        shareOnLinkedIn,
        copyPostLink,
        showNotification
    };
}