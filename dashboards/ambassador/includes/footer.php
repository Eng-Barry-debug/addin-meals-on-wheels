        </main>
    </div>

    <script>
        // Ambassador-specific JavaScript functions can go here

        // Function to handle referral sharing
        function shareReferralLink() {
            const referralLink = '<?php echo "https://addinsmeals.com/register?ref=" . $_SESSION['user_id']; ?>';

            if (navigator.share) {
                navigator.share({
                    title: 'Join Addins Meals on Wheels',
                    text: 'Get delicious meals delivered to your doorstep. Use my referral link to get started!',
                    url: referralLink
                });
            } else {
                // Fallback for browsers that don't support Web Share API
                navigator.clipboard.writeText(referralLink).then(function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Link Copied!',
                        text: 'Referral link copied to clipboard',
                        showConfirmButton: false,
                        timer: 2000
                    });
                });
            }
        }

        // Function to generate referral code
        function generateReferralCode() {
            const code = 'AMB<?php echo $_SESSION['user_id']; ?>';
            navigator.clipboard.writeText(code).then(function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Code Copied!',
                    text: 'Referral code copied to clipboard',
                    showConfirmButton: false,
                    timer: 2000
                });
            });
        }
    </script>
</body>
</html>
