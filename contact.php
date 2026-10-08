<?php
$pageTitle = 'Contact Gloy Skincare | Support & Inquiries';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container section" style="padding-top:60px;">
  <div class="section-header" style="max-width:700px;">
    <span class="bento-tag">Get In Touch</span>
    <h1 class="section-title" style="font-size:3rem; color:var(--text-heading);">We'd Love to Hear From You</h1>
    <p class="section-desc">Have questions about Gloy products, your routine, or order status? Our skincare team is here to help.</p>
  </div>

  <div class="contact-grid" style="display:grid; grid-template-columns: 1.2fr 1fr; gap:50px; align-items:start;">
    
    <!-- Contact Form (AJAX Submission) -->
    <div class="contact-card" style="background:var(--bg-surface); border:1.5px solid var(--border-color); border-radius:var(--radius-lg); padding:36px; box-shadow:var(--shadow-sm);">
      <h3 style="font-size:1.6rem; margin-bottom:24px; color:var(--text-heading);">Send Us a Message</h3>

      <form id="contact-form" novalidate>
        <div class="form-row-2col" style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px;">
          <div>
            <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px; color:var(--text-heading);">Your Name *</label>
            <input type="text" name="name" class="input-field" placeholder="e.g. Ayesha Khan" oninput="this.value = this.value.replace(/[0-9]/g, '')" pattern="[a-zA-Z\s\.\'\-]{3,60}" title="Letters only, no numbers" required>
            <small style="color:var(--text-muted); font-size:0.75rem; display:block; margin-top:4px;">Alphabetic letters only</small>
          </div>

          <div>
            <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px; color:var(--text-heading);">Email Address *</label>
            <input type="email" name="email" class="input-field" placeholder="e.g. ayesha@gmail.com" required>
            <small style="color:var(--text-muted); font-size:0.75rem; display:block; margin-top:4px;">Valid domain required (e.g. @gmail.com)</small>
          </div>
        </div>

        <div style="margin-bottom:20px;">
          <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px; color:var(--text-heading);">Subject *</label>
          <input type="text" name="subject" class="input-field" placeholder="Order status, skincare routine consultation..." minlength="3" maxlength="150" required>
        </div>

        <div style="margin-bottom:24px;">
          <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px; color:var(--text-heading);">Message *</label>
          <textarea name="message" class="input-field" rows="5" placeholder="How can we assist your skin journey today? (Min 10 characters)" minlength="10" maxlength="3000" required></textarea>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;">
          <i class="bi bi-send-fill me-1"></i> Send Message
        </button>
      </form>
    </div>

    <!-- Contact Info & Business Hours Sidebar -->
    <div>
      <div style="background:var(--bg-surface); border:1.5px solid var(--border-color); border-radius:var(--radius-lg); padding:36px; margin-bottom:32px; box-shadow:var(--shadow-sm);">
        <h3 style="font-size:1.4rem; margin-bottom:24px; color:var(--text-heading);">Contact Information</h3>
        
        <div style="display:flex; flex-direction:column; gap:24px; font-size:0.95rem;">
          <div style="display:flex; gap:14px; align-items:flex-start;">
            <div style="width:40px; height:40px; border-radius:50%; background:rgba(99,102,241,0.12); color:var(--primary-purple); display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0;">
              <i class="bi bi-envelope-fill"></i>
            </div>
            <div>
              <strong style="color:var(--text-heading); display:block; margin-bottom:2px;">Email Assistance:</strong>
              <a href="mailto:gloyskincare@gmail.com" style="color:var(--primary-purple); font-weight:600;">gloyskincare@gmail.com</a>
            </div>
          </div>

          <div style="display:flex; gap:14px; align-items:flex-start;">
            <div style="width:40px; height:40px; border-radius:50%; background:rgba(99,102,241,0.12); color:var(--primary-purple); display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0;">
              <i class="bi bi-telephone-fill"></i>
            </div>
            <div>
              <strong style="color:var(--text-heading); display:block; margin-bottom:2px;">Contact Number / WhatsApp:</strong>
              <a href="https://wa.me/923168882788" target="_blank" style="color:var(--primary-purple); font-weight:600; font-size:1.1rem;">0316 8882788</a>
            </div>
          </div>

          <div style="display:flex; gap:14px; align-items:flex-start;">
            <div style="width:40px; height:40px; border-radius:50%; background:rgba(99,102,241,0.12); color:var(--primary-purple); display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0;">
              <i class="bi bi-facebook"></i>
            </div>
            <div>
              <strong style="color:var(--text-heading); display:block; margin-bottom:2px;">Facebook Page:</strong>
              <a href="https://www.facebook.com/profile.php?id=61566772826261" target="_blank" rel="noopener" style="color:var(--primary-purple);">facebook.com/GloySkincare</a>
            </div>
          </div>

          <div style="display:flex; gap:14px; align-items:flex-start;">
            <div style="width:40px; height:40px; border-radius:50%; background:rgba(99,102,241,0.12); color:var(--primary-purple); display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0;">
              <i class="bi bi-instagram"></i>
            </div>
            <div>
              <strong style="color:var(--text-heading); display:block; margin-bottom:2px;">Instagram:</strong>
              <a href="https://www.instagram.com/gloyskincare" target="_blank" rel="noopener" style="color:var(--primary-purple);">instagram.com/gloyskincare</a>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- AJAX Contact Form Script -->
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const contactForm = document.getElementById('contact-form');
    if (contactForm) {
      contactForm.addEventListener('submit', (e) => {
        e.preventDefault();

        const nameInput = contactForm.querySelector('input[name="name"]');
        const emailInput = contactForm.querySelector('input[name="email"]');
        const subjectInput = contactForm.querySelector('input[name="subject"]');
        const messageInput = contactForm.querySelector('textarea[name="message"]');

        const name = nameInput.value.trim();
        const email = emailInput.value.trim();
        const subject = subjectInput.value.trim();
        const message = messageInput.value.trim();

        // 1. Client-Side Name Validation (Strictly No Digits)
        if (!name || name.length < 3 || /[0-9]/.test(name) || !/^[a-zA-Z\s\.\'\-]+$/.test(name)) {
          window.showToast('Please enter a valid name with letters only (no numbers allowed).', 'error');
          nameInput.focus();
          return;
        }

        // 2. Client-Side Email Validation (Letters in username required, valid domain & TLD)
        const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        const emailParts = email.split('@');
        if (!email || !emailPattern.test(email) || emailParts.length !== 2 || !/[a-zA-Z]/.test(emailParts[0]) || !/[a-zA-Z]/.test(emailParts[1])) {
          window.showToast('Email username cannot be only numbers. Please include letters (e.g. ayesha@gmail.com or ayesha123@gmail.com).', 'error');
          emailInput.focus();
          return;
        }

        // 3. Client-Side Subject Validation
        if (!subject || subject.length < 3) {
          window.showToast('Subject must be at least 3 characters long.', 'error');
          subjectInput.focus();
          return;
        }

        // 4. Client-Side Message Validation
        if (!message || message.length < 10) {
          window.showToast('Message must be at least 10 characters long.', 'error');
          messageInput.focus();
          return;
        }

        const submitBtn = contactForm.querySelector('button[type="submit"]');
        const originalBtnHtml = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Sending...';

        fetch('api/submit_contact.php', {
          method: 'POST',
          body: new FormData(contactForm)
        })
        .then(res => res.json())
        .then(data => {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalBtnHtml;
          if (data.success) {
            window.showToast(data.message, 'success');
            contactForm.reset();
          } else {
            window.showToast(data.message, 'error');
          }
        })
        .catch(err => {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalBtnHtml;
          window.showToast('Network error submitting contact form. Please try again.', 'error');
        });
      });
    }
  });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
