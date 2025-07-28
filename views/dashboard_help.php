<?php
// Help tab content
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-question-circle"></i> Help Center</h3>
    </div>
    <div class="help-content" style="padding: 2rem;">
        <!-- Search Bar -->
        <div style="margin-bottom: 2rem; display: flex; align-items: center; gap: 1rem;">
            <input type="text" id="helpSearch" class="form-control" placeholder="Search help topics..." style="flex: 1; max-width: 400px;">
            <button class="btn btn-primary" onclick="searchHelp()"><i class="fas fa-search"></i> Search</button>
        </div>

        <!-- FAQ Accordion -->
        <div class="accordion" id="faqAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header" id="faq1">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1" aria-expanded="true" aria-controls="collapse1">
                        How do I book an event?
                    </button>
                </h2>
                <div id="collapse1" class="accordion-collapse collapse show" aria-labelledby="faq1" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Go to the Events tab, click on your desired event, and use the "Book Now" button. Fill in the required details and confirm your booking.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="faq2">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2" aria-expanded="false" aria-controls="collapse2">
                        How can I view or cancel my bookings?
                    </button>
                </h2>
                <div id="collapse2" class="accordion-collapse collapse" aria-labelledby="faq2" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Go to the Bookings tab to see all your bookings. To cancel, click the "Cancel" button next to the booking you wish to cancel.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="faq3">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3" aria-expanded="false" aria-controls="collapse3">
                        How do I update my profile or change my password?
                    </button>
                </h2>
                <div id="collapse3" class="accordion-collapse collapse" aria-labelledby="faq3" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Go to the Profile or Settings tab to update your personal information or change your password.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="faq4">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse4" aria-expanded="false" aria-controls="collapse4">
                        What payment methods are accepted?
                    </button>
                </h2>
                <div id="collapse4" class="accordion-collapse collapse" aria-labelledby="faq4" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Currently, we accept bank transfers to our official account. Please follow the payment instructions during booking.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="faq5">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse5" aria-expanded="false" aria-controls="collapse5">
                        How do I contact support?
                    </button>
                </h2>
                <div id="collapse5" class="accordion-collapse collapse" aria-labelledby="faq5" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Use the contact form below or email us at <a href="mailto:support@bhavyaevent.com">support@bhavyaevent.com</a>.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="faq6">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse6" aria-expanded="false" aria-controls="collapse6">
                        <i class="fas fa-unlock-alt me-2"></i> I forgot my password. How do I reset it?
                    </button>
                </h2>
                <div id="collapse6" class="accordion-collapse collapse" aria-labelledby="faq6" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Click on the <b>"Forgot Password?"</b> link on the login page. Enter your registered email address and follow the instructions to reset your password.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="faq7">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse7" aria-expanded="false" aria-controls="collapse7">
                        <i class="fas fa-sign-in-alt me-2"></i> I'm having trouble logging in. What should I do?
                    </button>
                </h2>
                <div id="collapse7" class="accordion-collapse collapse" aria-labelledby="faq7" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Double-check your email and password. If you still can't log in, try resetting your password or contact support for assistance.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="faq8">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse8" aria-expanded="false" aria-controls="collapse8">
                        <i class="fas fa-user-plus me-2"></i> How do I create a new account?
                    </button>
                </h2>
                <div id="collapse8" class="accordion-collapse collapse" aria-labelledby="faq8" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Go to the registration page, fill in your details, and submit the form. You'll receive a confirmation email if required.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="faq9">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse9" aria-expanded="false" aria-controls="collapse9">
                        <i class="fas fa-bell me-2"></i> How do I receive notifications about events?
                    </button>
                </h2>
                <div id="collapse9" class="accordion-collapse collapse" aria-labelledby="faq9" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Make sure you are logged in. Notifications will appear in your dashboard and you can view all notifications from the notification bell icon.
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Support Form -->
        <div style="margin-top: 2.5rem;">
            <h4><i class="fas fa-envelope"></i> Contact Support</h4>
            <form id="helpContactForm" action="https://api.web3forms.com/submit" method="POST" style="max-width: 500px;" class="help-web3form">
                <input type="hidden" name="access_key" value="af53f1a0-b23e-483c-b82a-3286172da6a2">
                <input type="hidden" name="email" value="vabyaevents@gmail.com">
                <div class="mb-3">
                    <label for="helpName" class="form-label">Your Name</label>
                    <input type="text" class="form-control" id="helpName" name="name" required>
                </div>
                <div class="mb-3">
                    <label for="helpEmail" class="form-label">Your Email</label>
                    <input type="email" class="form-control" id="helpEmail" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="helpMessage" class="form-label">Message</label>
                    <textarea class="form-control" id="helpMessage" name="message" rows="4" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Send Message</button>
                <div id="helpFormStatus" style="margin-top: 1rem;"></div>
            </form>
        </div>

        <!-- Resources -->
        <div style="margin-top: 2.5rem;">
            <h4><i class="fas fa-book"></i> Resources & Documentation</h4>
            <ul>
                <li><a href="https://bhavyaevent.com/docs/user-guide" target="_blank">User Guide</a></li>
                <li><a href="https://bhavyaevent.com/docs/faq" target="_blank">Full FAQ</a></li>
                <li><a href="mailto:support@bhavyaevent.com">Email Support</a></li>
            </ul>
        </div>
    </div> <!-- end of .help-content -->
    <!-- Contact & Support (moved outside .help-content for correct flex display) -->
    <div style="margin-top: 2.5rem; background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(27,60,83,0.08); padding: 2rem 2.5rem; max-width: 520px; margin-left: auto; margin-right: auto; color: #111;">
        <h4 style="color: #1B3C53;"><i class="fas fa-user-cog"></i> Contact & Support</h4>
        <ul class="contact-support-list">
            <li><b>Developer:</b>&nbsp;Bhavya Team</li>
            <li><b>Email:</b>&nbsp;<a href="mailto:supportbhavya@gmail.com" style="color: #111;">supportbhavya@gmail.com</a></li>
            <li><b>Documentation:</b>&nbsp;<a href="https://docs.google.com/document/d/1Wx97k1b23R9VoG6ur2vqe8QV8SFN-vKG9pYyiJRHYUc/edit?tab=t.0" target="_blank" style="color: #111;">User Guide</a></li>
        </ul>
        <p style="margin-top: 0.5rem; color: #666;">For technical issues, feature requests, or urgent support, please email the developer directly or use the support form above.</p>
    </div>
</div>

<script>
// FAQ Search
function searchHelp() {
    const query = document.getElementById('helpSearch').value.toLowerCase();
    const items = document.querySelectorAll('#faqAccordion .accordion-item');
    let found = false;
    items.forEach(item => {
        const header = item.querySelector('.accordion-button').textContent.toLowerCase();
        const body = item.querySelector('.accordion-body').textContent.toLowerCase();
        if (header.includes(query) || body.includes(query)) {
            item.style.display = '';
            found = true;
        } else {
            item.style.display = 'none';
        }
    });
    if (!found && query) {
        if (!document.getElementById('noHelpFound')) {
            const noResult = document.createElement('div');
            noResult.id = 'noHelpFound';
            noResult.className = 'alert alert-warning mt-3';
            noResult.textContent = 'No help topics found for your search.';
            document.getElementById('faqAccordion').appendChild(noResult);
        }
    } else {
        const noResult = document.getElementById('noHelpFound');
        if (noResult) noResult.remove();
    }
}

// Contact Support Form Submission (AJAX simulation)
function submitHelpForm(event) {
    event.preventDefault();
    const statusDiv = document.getElementById('helpFormStatus');
    statusDiv.innerHTML = '<span class="text-info">Sending...</span>';
    setTimeout(() => {
        statusDiv.innerHTML = '<span class="text-success">Thank you! Your message has been sent. Our support team will contact you soon.</span>';
        document.getElementById('helpContactForm').reset();
    }, 1200);
    return false;
}
</script>

<style>
.help-content {
    padding: 2.5rem 2rem 2rem 2rem;
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 6px 32px rgba(39, 174, 96, 0.08), 0 1.5px 6px rgba(51, 51, 51, 0.04);
    max-width: 900px;
    margin: 2rem auto;
}
.help-content .accordion-button {
    font-weight: 600;
    font-size: 1.1rem;
    border-radius: 10px 10px 0 0;
    background: var(--neutral-light, #F2F2F2);
    color: var(--primary, #27AE60);
    transition: background 0.2s, color 0.2s;
}
.help-content .accordion-item {
    border-radius: 12px;
    margin-bottom: 1.1rem;
    border: 1px solid #e5e7eb;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(39, 174, 96, 0.04);
}
.help-content .accordion-button:not(.collapsed) {
    background: var(--primary, #27AE60);
    color: #fff;
}
.help-content .accordion-button.collapsed {
    background: var(--neutral-light, #F2F2F2);
    color: var(--primary, #27AE60);
}
.help-content .accordion-body {
    background: #fafbfc;
    color: var(--neutral-dark, #333333);
    font-size: 1.04rem;
    padding: 1.2rem 1.5rem;
}
.help-content .btn-primary {
    background: var(--primary, #27AE60);
    border: none;
    font-weight: 600;
    border-radius: 8px;
    padding: 0.6rem 1.5rem;
    font-size: 1.08rem;
    box-shadow: 0 2px 8px rgba(39, 174, 96, 0.08);
    transition: background 0.2s;
}
.help-content .btn-primary:hover {
    background: var(--secondary, #F2994A);
    color: #fff;
}
.help-content input, .help-content textarea {
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    font-size: 1.04rem;
    padding: 0.55rem 1rem;
}
.help-content input:focus, .help-content textarea:focus {
    border-color: var(--primary, #27AE60);
    box-shadow: 0 0 0 2px #e0f7ea;
}
.help-content ul {
    margin-top: 1rem;
    margin-bottom: 0;
    padding-left: 1.2rem;
}
.help-content ul li {
    margin-bottom: 0.5rem;
}
.help-content a {
    color: var(--accent, #9B51E0);
    text-decoration: underline;
    font-weight: 500;
}
.help-content a:hover {
    color: var(--secondary, #F2994A);
}
.help-content h4 {
    margin-top: 2.5rem;
    margin-bottom: 1.2rem;
    color: var(--primary, #27AE60);
    font-weight: 700;
}
.help-content .form-label {
    font-weight: 500;
    color: var(--neutral-dark, #333333);
}
#helpFormStatus {
    min-height: 1.5rem;
}
.contact-support-list {
    font-size: 1.08em;
    line-height: 2;
    list-style: none;
    padding-left: 0;
    max-width: 500px;
}
.contact-support-list li {
    display: flex !important;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
}
.contact-support-list b {
    min-width: 110px;
}
.contact-support-list a {
    color: #1B3C53;
    text-decoration: underline;
    font-weight: 500;
}
</style>