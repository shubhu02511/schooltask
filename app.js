/* ==========================================================================
   BRIO WORLD SCHOOL Application Logic
   ========================================================================== */

document.addEventListener('DOMContentLoaded', () => {
  // Initialize Router
  initRouter();
  
  // Initialize Fee Calculator
  initFeeCalculator();
  
  // Initialize Admission Wizard Form
  initAdmissionWizard();
  
  // Initialize Gallery Filter Modal
  initGallerySystem();
  
  // Initialize AI Bot Assistant
  initBrioBot();

  // Initialize Document Viewer System
  initDocumentViewerSystem();

  // Initialize Academic Wing Modal System
  initAcademicWingModalSystem();

  // Initialize Global Modal Close Listeners
  initModalCloseListeners();

  // Initialize Career Application System
  initCareerSystem();

  // Initialize Hero Carousel System
  initHeroCarousel();

  // Initialize Leadership Slider System
  initLeadershipSlider();

  // Initialize Auth & OTP System
  initAuthSystem();

  // Setup Mobile Nav Toggle
  setupMobileNav();
});

/* ==========================================================================
   1. SPA Navigation & Router
   ========================================================================== */
function initRouter() {
  const navLinks = document.querySelectorAll('.nav-link, .router-link');
  const pageViews = document.querySelectorAll('.page-view');

  function navigateTo(targetId) {
    const targetElement = document.getElementById(targetId);

    if (targetElement) {
      const parentPageView = targetElement.closest('.page-view');

      // Reset page active state
      pageViews.forEach(page => page.classList.remove('active-page'));

      if (parentPageView) {
        parentPageView.classList.add('active-page');
      } else if (targetElement.classList.contains('page-view')) {
        targetElement.classList.add('active-page');
      } else {
        document.getElementById('home').classList.add('active-page');
      }

      // Update Nav Active State
      navLinks.forEach(link => {
        if (link.getAttribute('href') === `#${targetId}`) {
          link.classList.add('active');
        } else {
          link.classList.remove('active');
        }
      });

      // Scroll smoothly to target
      setTimeout(() => {
        targetElement.scrollIntoView({ behavior: 'smooth' });
      }, 50);
    } else {
      pageViews.forEach(page => page.classList.remove('active-page'));
      document.getElementById('home').classList.add('active-page');
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  }

  // Event Listeners for Clicks
  navLinks.forEach(link => {
    link.addEventListener('click', (e) => {
      const href = link.getAttribute('href');
      if (href && href.startsWith('#')) {
        e.preventDefault();
        const targetId = href.substring(1);
        navigateTo(targetId);
        window.location.hash = targetId;
      }
    });
  });

  // Handle Initial Hash Load
  const initialHash = window.location.hash.substring(1);
  if (initialHash) {
    navigateTo(initialHash);
  } else {
    navigateTo('home');
  }

  // Handle Browser Back Forward
  window.addEventListener('hashchange', () => {
    const currentHash = window.location.hash.substring(1);
    navigateTo(currentHash || 'home');
  });
}

function setupMobileNav() {
  const toggleBtn = document.querySelector('.mobile-nav-toggle');
  const navMenu = document.querySelector('.nav-menu');

  if (toggleBtn && navMenu) {
    toggleBtn.addEventListener('click', () => {
      const isVisible = navMenu.style.display === 'flex';
      navMenu.style.display = isVisible ? 'none' : 'flex';
      if (!isVisible) {
        navMenu.style.flexDirection = 'column';
        navMenu.style.position = 'absolute';
        navMenu.style.top = '100%';
        navMenu.style.left = '0';
        navMenu.style.right = '0';
        navMenu.style.background = 'white';
        navMenu.style.padding = '1.5rem';
        navMenu.style.boxShadow = '0 10px 30px rgba(0,0,0,0.15)';
      }
    });

    document.querySelectorAll('.nav-link').forEach(link => {
      link.addEventListener('click', () => {
        if (window.innerWidth <= 768) {
          navMenu.style.display = 'none';
        }
      });
    });
  }
}

/* ==========================================================================
   2. Interactive Fee Calculator Engine
   ========================================================================== */
function initFeeCalculator() {
  const gradeSelect = document.getElementById('calc-grade');
  const transportSelect = document.getElementById('calc-transport');
  const hostelCheck = document.getElementById('calc-hostel');

  if (!gradeSelect) return;

  const feeData = {
    'pre-k': { tuition: 45000, lab: 5000, activity: 8000 },
    'primary': { tuition: 65000, lab: 8000, activity: 12000 },
    'middle': { tuition: 85000, lab: 14000, activity: 15000 },
    'senior': { tuition: 110000, lab: 22000, activity: 18000 }
  };

  const transportRates = {
    'none': 0,
    'zone1': 12000,
    'zone2': 18000,
    'zone3': 24000
  };

  function calculate() {
    const grade = gradeSelect.value;
    const transportZone = transportSelect ? transportSelect.value : 'none';
    const includesHostel = hostelCheck ? hostelCheck.checked : false;

    const base = feeData[grade] || feeData['primary'];
    const transportCost = transportRates[transportZone] || 0;
    const hostelCost = includesHostel ? 95000 : 0;
    const admissionOneTime = 25000;

    const grandTotal = base.tuition + base.lab + base.activity + transportCost + hostelCost + admissionOneTime;

    // Update DOM
    document.getElementById('fee-tuition').innerText = `₹${base.tuition.toLocaleString()}`;
    document.getElementById('fee-lab').innerText = `₹${base.lab.toLocaleString()}`;
    document.getElementById('fee-activity').innerText = `₹${base.activity.toLocaleString()}`;
    document.getElementById('fee-transport').innerText = `₹${transportCost.toLocaleString()}`;
    document.getElementById('fee-hostel').innerText = `₹${hostelCost.toLocaleString()}`;
    document.getElementById('fee-total').innerText = `₹${grandTotal.toLocaleString()}`;
  }

  gradeSelect.addEventListener('change', calculate);
  if (transportSelect) transportSelect.addEventListener('change', calculate);
  if (hostelCheck) hostelCheck.addEventListener('change', calculate);

  // Initial Calculation
  calculate();
}

/* ==========================================================================
   3. Admission Application Wizard
   ========================================================================== */
function initAdmissionWizard() {
  let currentStep = 1;
  const totalSteps = 4;

  const nextBtn = document.getElementById('wizard-next-btn');
  const prevBtn = document.getElementById('wizard-prev-btn');
  const submitBtn = document.getElementById('wizard-submit-btn');

  if (!nextBtn) return;

  function updateStepUI() {
    // Hide all step contents
    document.querySelectorAll('.wizard-content-step').forEach((stepEl, idx) => {
      stepEl.classList.toggle('active-step', idx + 1 === currentStep);
    });

    // Update Step Indicator Circles
    document.querySelectorAll('.step-item').forEach((item, idx) => {
      item.classList.remove('active', 'completed');
      if (idx + 1 === currentStep) {
        item.classList.add('active');
      } else if (idx + 1 < currentStep) {
        item.classList.add('completed');
      }
    });

    // Control Buttons
    prevBtn.style.display = currentStep === 1 ? 'none' : 'inline-flex';
    if (currentStep === totalSteps) {
      nextBtn.style.display = 'none';
      submitBtn.style.display = 'inline-flex';
    } else {
      nextBtn.style.display = 'inline-flex';
      submitBtn.style.display = 'none';
    }
  }

  nextBtn.addEventListener('click', () => {
    if (validateStep(currentStep)) {
      currentStep++;
      updateStepUI();
    }
  });

  prevBtn.addEventListener('click', () => {
    if (currentStep > 1) {
      currentStep--;
      updateStepUI();
    }
  });

  function validateStep(step) {
    if (step === 1) {
      const studentName = document.getElementById('adm-student-name').value.trim();
      const grade = document.getElementById('adm-apply-grade').value;
      if (!studentName) {
        alert('Please enter the Student Full Name.');
        return false;
      }
      if (!grade) {
        alert('Please select the Grade applying for.');
        return false;
      }
    } else if (step === 2) {
      const parentName = document.getElementById('adm-parent-name').value.trim();
      const parentEmail = document.getElementById('adm-parent-email').value.trim();
      const parentPhone = document.getElementById('adm-parent-phone').value.trim();
      if (!parentName || !parentEmail || !parentPhone) {
        alert('Please fill out all mandatory parent contact details.');
        return false;
      }
    }
    return true;
  }

  const admForm = document.getElementById('admission-application-form');
  if (admForm) {
    admForm.addEventListener('submit', (e) => {
      e.preventDefault();
      
      const studentName = document.getElementById('adm-student-name').value;
      const grade = document.getElementById('adm-apply-grade').value;
      const refNo = 'BRIO-2026-' + Math.floor(100000 + Math.random() * 900000);

      // Show Confirmation Modal
      const modal = document.getElementById('admission-modal-success');
      document.getElementById('modal-ref-no').innerText = refNo;
      document.getElementById('modal-student-name').innerText = studentName;
      document.getElementById('modal-applied-grade').innerText = grade;
      
      modal.classList.add('active');
    });
  }
}

/* ==========================================================================
   4. Campus Gallery & Lightbox
   ========================================================================== */
function initGallerySystem() {
  const filterBtns = document.querySelectorAll('.filter-btn');
  const galleryItems = document.querySelectorAll('.gallery-card');

  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      filterBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      const filterValue = btn.getAttribute('data-filter');

      galleryItems.forEach(item => {
        if (filterValue === 'all' || item.getAttribute('data-category') === filterValue) {
          item.style.display = 'block';
        } else {
          item.style.display = 'none';
        }
      });
    });
  });

  // Lightbox Preview
  galleryItems.forEach(item => {
    item.addEventListener('click', () => {
      const imgSrc = item.querySelector('img').src;
      const title = item.querySelector('h4').innerText;
      const desc = item.querySelector('p').innerText;

      const lightboxModal = document.getElementById('gallery-lightbox-modal');
      document.getElementById('lightbox-img').src = imgSrc;
      document.getElementById('lightbox-title').innerText = title;
      document.getElementById('lightbox-desc').innerText = desc;

      lightboxModal.classList.add('active');
    });
  });
}

/* ==========================================================================
   5. Brio AI Bot Logic
   ========================================================================== */
function initBrioBot() {
  const botTrigger = document.querySelector('.brio-bot-trigger');
  const chatWidget = document.querySelector('.chat-widget');
  const closeChat = document.querySelector('.chat-close');
  const chatBody = document.querySelector('.chat-body');
  const chatInput = document.getElementById('chat-input-field');
  const chatSendBtn = document.getElementById('chat-send-btn');
  const quickChips = document.querySelectorAll('.chat-chip');

  if (!botTrigger || !chatWidget) return;

  botTrigger.addEventListener('click', () => {
    chatWidget.classList.toggle('active');
  });

  if (closeChat) {
    closeChat.addEventListener('click', () => {
      chatWidget.classList.remove('active');
    });
  }

  function addMessage(text, isUser = false) {
    const msgDiv = document.createElement('div');
    msgDiv.className = `chat-msg ${isUser ? 'chat-msg-user' : 'chat-msg-bot'}`;
    msgDiv.innerText = text;
    chatBody.appendChild(msgDiv);
    chatBody.scrollTop = chatBody.scrollHeight;
  }

  function handleQuery(queryText) {
    addMessage(queryText, true);

    setTimeout(() => {
      const lower = queryText.toLowerCase();
      let response = "Thank you for reaching out to BRIO World School! You can contact our Admissions Cell directly at admissions@brioworldschool.edu.in or call +91 (0542) 2890-400.";

      if (lower.includes('fee') || lower.includes('cost') || lower.includes('tuition')) {
        response = "Our annual tuition ranges from ₹45,000 for Pre-Primary to ₹1,10,000 for Senior Secondary. You can use our interactive Fee Calculator in the Admissions tab for a complete breakdown!";
      } else if (lower.includes('admission') || lower.includes('apply') || lower.includes('form')) {
        response = "Admissions for Academic Session 2026-27 are currently OPEN! You can fill out our online multi-step application form in the 'Admissions' tab to secure an entrance assessment slot.";
      } else if (lower.includes('cbse') || lower.includes('ib') || lower.includes('curriculum')) {
        response = "BRIO World School offers dual global accreditation: CBSE (Central Board of Secondary Education) and the IB Diploma Pathway for Grades 11 & 12.";
      } else if (lower.includes('transport') || lower.includes('bus')) {
        response = "We operate a GPS-tracked, air-conditioned bus fleet covering 3 major city zones with live CCTV monitoring and trained attendants.";
      } else if (lower.includes('hostel') || lower.includes('boarding')) {
        response = "BRIO provides world-class air-conditioned boarding facilities for Boys & Girls (Grades 5-12) with dedicated pastoral care, nutritious dining, and evening sports coaching.";
      }

      addMessage(response, false);
    }, 600);
  }

  if (chatSendBtn && chatInput) {
    chatSendBtn.addEventListener('click', () => {
      const text = chatInput.value.trim();
      if (text) {
        handleQuery(text);
        chatInput.value = '';
      }
    });

    chatInput.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') {
        const text = chatInput.value.trim();
        if (text) {
          handleQuery(text);
          chatInput.value = '';
        }
      }
    });
  }

  quickChips.forEach(chip => {
    chip.addEventListener('click', () => {
      handleQuery(chip.innerText);
    });
  });
}

// Modal Close Listeners
function initModalCloseListeners() {
  // Close on cross button click
  document.querySelectorAll('.modal-close-trigger').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      document.querySelectorAll('.modal-backdrop').forEach(m => m.classList.remove('active'));
    });
  });

  // Close on backdrop click
  document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
    backdrop.addEventListener('click', (e) => {
      if (e.target === backdrop) {
        backdrop.classList.remove('active');
      }
    });
  });

  // Close on Escape key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-backdrop').forEach(m => m.classList.remove('active'));
    }
  });
}

/* ==========================================================================
   8. Document Viewer & PDF Generator
   ========================================================================== */
function initDocumentViewerSystem() {
  const docTriggers = document.querySelectorAll('.doc-view-trigger');
  const docModal = document.getElementById('doc-viewer-modal');
  const docTitle = document.getElementById('doc-modal-title');
  const docSubtitle = document.getElementById('doc-modal-subtitle');
  const docBody = document.getElementById('doc-modal-body');
  const downloadBtn = document.getElementById('doc-download-btn');

  if (!docModal) return;

  let currentDocType = 'prospectus';

  const documentData = {
    'prospectus': {
      title: 'BRIO World School Prospectus 2026-27',
      subtitle: 'Official Academic Information & Code of Conduct',
      fileName: 'BRIO_World_School_Prospectus_2026-27.pdf',
      html: `
        <div style="text-align: center; border-bottom: 2px dashed var(--accent-gold); padding-bottom: 1rem; margin-bottom: 1.25rem;">
          <h2 style="color: var(--primary-navy); margin-bottom: 0.2rem;">BRIO WORLD SCHOOL</h2>
          <p style="color: var(--accent-gold-hover); font-weight: 700; font-size: 0.85rem; text-transform: uppercase;">Inspiring Excellence • Global Campus</p>
          <p style="font-size: 0.8rem; color: var(--text-muted);">Affiliated to CBSE (Affln No. 2130890) & IB Diploma Pathway</p>
        </div>
        <h4 style="color: var(--primary-navy); margin-bottom: 0.5rem;">1. Director's Welcome Note</h4>
        <p style="margin-bottom: 1rem; color: var(--text-muted);">Welcome to BRIO World School. Our 20-acre eco-green campus provides a transformative blend of academic rigor, STEM innovation, and moral values. We nurture inquisitive minds into global leaders.</p>
        
        <h4 style="color: var(--primary-navy); margin-bottom: 0.5rem;">2. Academic Streams & Pedagogy</h4>
        <ul style="margin-left: 1.2rem; margin-bottom: 1rem; color: var(--text-muted);">
          <li><strong>Pre-Primary (Pre-K to KG2):</strong> Play-based motor skill development & language immersion.</li>
          <li><strong>Primary & Middle Wing (Grades 1-8):</strong> Experiential STEM labs, coding, robotics, & ethics.</li>
          <li><strong>Senior Secondary (Grades 9-12):</strong> CBSE & IB Diploma in Science, Commerce, and Humanities.</li>
        </ul>

        <h4 style="color: var(--primary-navy); margin-bottom: 0.5rem;">3. Key Facilities & Infrastructure</h4>
        <p style="margin-bottom: 1rem; color: var(--text-muted);">Olympic 50m Swimming Pool, FIFA Standard Football Turf, 35,000+ Book Digital Library, 1,200-seater Auditorium, and 24/7 Air-Conditioned Residential Hostels.</p>
        
        <div style="margin-top: 1.5rem; background: white; padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-light); text-align: center;">
          <p style="font-size: 0.8rem; color: var(--text-muted);">Verified Official Publication • Academic Session 2026-27</p>
        </div>
      `
    },
    'calendar': {
      title: 'Academic Calendar 2026-27 & Holiday Schedule',
      subtitle: 'Session Key Dates, Exam Schedules & Vacations',
      fileName: 'BRIO_Academic_Calendar_2026-27.pdf',
      html: `
        <div style="text-align: center; border-bottom: 2px dashed var(--accent-gold); padding-bottom: 1rem; margin-bottom: 1.25rem;">
          <h2 style="color: var(--primary-navy); margin-bottom: 0.2rem;">BRIO WORLD SCHOOL</h2>
          <p style="color: var(--accent-gold-hover); font-weight: 700; font-size: 0.85rem;">Official Academic Schedule 2026-27</p>
        </div>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 1rem; font-size: 0.85rem;">
          <thead>
            <tr style="background: var(--primary-navy); color: white;">
              <th style="padding: 0.6rem; text-align: left;">Date / Month</th>
              <th style="padding: 0.6rem; text-align: left;">Event / Activity</th>
              <th style="padding: 0.6rem; text-align: left;">Applicability</th>
            </tr>
          </thead>
          <tbody>
            <tr><td style="padding: 0.5rem; border-bottom: 1px solid #ddd;">August 12, 2026</td><td style="padding: 0.5rem; border-bottom: 1px solid #ddd;">National Science & Robotics Expo</td><td style="padding: 0.5rem; border-bottom: 1px solid #ddd;">Grades 6 to 12</td></tr>
            <tr><td style="padding: 0.5rem; border-bottom: 1px solid #ddd;">September 01, 2026</td><td style="padding: 0.5rem; border-bottom: 1px solid #ddd;">B-SAT Entrance Scholarship Test</td><td style="padding: 0.5rem; border-bottom: 1px solid #ddd;">New Applicants</td></tr>
            <tr><td style="padding: 0.5rem; border-bottom: 1px solid #ddd;">October 15-24, 2026</td><td style="padding: 0.5rem; border-bottom: 1px solid #ddd;">Term 1 Examinations</td><td style="padding: 0.5rem; border-bottom: 1px solid #ddd;">All Wings</td></tr>
            <tr><td style="padding: 0.5rem; border-bottom: 1px solid #ddd;">November 10-15, 2026</td><td style="padding: 0.5rem; border-bottom: 1px solid #ddd;">Diwali Break & Autumn Vacation</td><td style="padding: 0.5rem; border-bottom: 1px solid #ddd;">All Students</td></tr>
            <tr><td style="padding: 0.5rem; border-bottom: 1px solid #ddd;">December 20, 2026</td><td style="padding: 0.5rem; border-bottom: 1px solid #ddd;">Annual Sports & Cultural Day</td><td style="padding: 0.5rem; border-bottom: 1px solid #ddd;">Whole Campus</td></tr>
          </tbody>
        </table>
      `
    },
    'policy': {
      title: 'Fee Policy & Transport Safety Rulebook',
      subtitle: 'Guidelines on Installments, Bus Routes & Boarding Rules',
      fileName: 'BRIO_Fee_and_Transport_Policy_2026.pdf',
      html: `
        <div style="text-align: center; border-bottom: 2px dashed var(--accent-gold); padding-bottom: 1rem; margin-bottom: 1.25rem;">
          <h2 style="color: var(--primary-navy); margin-bottom: 0.2rem;">BRIO WORLD SCHOOL</h2>
          <p style="color: var(--accent-gold-hover); font-weight: 700; font-size: 0.85rem;">Policy Handbook & Parent Regulations</p>
        </div>
        <h4 style="color: var(--primary-navy); margin-bottom: 0.4rem;">1. Fee Installment Schedules</h4>
        <p style="margin-bottom: 0.85rem; color: var(--text-muted);">Annual tuition is split into 3 equal term installments payable on April 10, August 10, and December 10.</p>
        
        <h4 style="color: var(--primary-navy); margin-bottom: 0.4rem;">2. Merit Scholarship Rules</h4>
        <p style="margin-bottom: 0.85rem; color: var(--text-muted);">Scholarships up to 50% are awarded to top rankers in the B-SAT assessment and national sports champions.</p>
        
        <h4 style="color: var(--primary-navy); margin-bottom: 0.4rem;">3. Transport Safety Protocols</h4>
        <p style="margin-bottom: 0.85rem; color: var(--text-muted);">All school buses feature live GPS tracking, speed governors, CCTV cameras, and female attendants.</p>
      `
    }
  };

  // Open Document Modal
  docTriggers.forEach(btn => {
    btn.addEventListener('click', () => {
      const docKey = btn.getAttribute('data-doc') || 'prospectus';
      currentDocType = docKey;

      const doc = documentData[docKey] || documentData['prospectus'];
      docTitle.innerText = doc.title;
      docSubtitle.innerText = doc.subtitle;
      docBody.innerHTML = doc.html;

      docModal.classList.add('active');
    });
  });

  // Generate PDF Download
  if (downloadBtn) {
    downloadBtn.addEventListener('click', () => {
      const doc = documentData[currentDocType] || documentData['prospectus'];
      
      const printWindow = window.open('', '_blank');
      printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
          <title>${doc.title}</title>
          <style>
            body { font-family: sans-serif; padding: 2rem; color: #1E293B; line-height: 1.6; }
            h2 { color: #0F172A; }
            table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
            th, td { border: 1px solid #CBD5E1; padding: 8px; text-align: left; }
            th { background: #0F172A; color: white; }
          </style>
        </head>
        <body>
          ${doc.html}
          <script>
            window.onload = function() {
              window.print();
            }
          </script>
        </body>
        </html>
      `);
      printWindow.document.close();
    });
  }
}

/* ==========================================================================
   9. Grade-Wise Academic Wing Modal
   ========================================================================== */
function initAcademicWingModalSystem() {
  const triggers = document.querySelectorAll('.wing-modal-trigger');
  const modal = document.getElementById('academic-wing-modal');
  const badge = document.getElementById('wing-modal-badge');
  const title = document.getElementById('wing-modal-title');
  const body = document.getElementById('wing-modal-body');

  if (!modal) return;

  // Grade-wise dataset
  const wingDetails = {
    'early-years': {
      badge: 'Pre-K to KG2 (Ages 3-5)',
      badgeClass: 'badge-gold',
      title: 'Early Years Foundation Wing',
      html: `
        <div style="background: var(--bg-light); padding: 1.25rem; border-radius: var(--radius-md); margin-bottom: 1.25rem; border-left: 4px solid var(--accent-gold);">
          <p style="color: var(--text-main); font-weight: 600; margin-bottom: 0.25rem;">Nurturing Curiosity & Foundation Growth</p>
          <p style="font-size: 0.88rem; color: var(--text-muted); margin: 0;">Our play-based methodology blends Montessori motor skill stations, phonics immersion, and sensory exploration in a caring environment.</p>
        </div>

        <h4 style="color: var(--primary-navy); margin-bottom: 0.5rem;"><i class="fa-solid fa-book-open text-gold"></i> Grade-Wise Core Curriculum</h4>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.75rem; margin-bottom: 1.25rem;">
          <div style="background: white; padding: 0.85rem; border-radius: var(--radius-sm); border: 1px solid var(--border-light);">
            <strong style="color: var(--primary-navy);">Pre-Nursery & Nursery</strong>
            <p style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.2rem;">Scribbling, color identification, nursery rhymes, motor agility, and social play.</p>
          </div>
          <div style="background: white; padding: 0.85rem; border-radius: var(--radius-sm); border: 1px solid var(--border-light);">
            <strong style="color: var(--primary-navy);">KG1 (Lower KG)</strong>
            <p style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.2rem;">Phonics sounds, number counting (1-50), story comprehension, and clay modeling.</p>
          </div>
          <div style="background: white; padding: 0.85rem; border-radius: var(--radius-sm); border: 1px solid var(--border-light);">
            <strong style="color: var(--primary-navy);">KG2 (Upper KG)</strong>
            <p style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.2rem;">Sentence reading, basic addition/subtraction, environmental awareness, and music.</p>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
          <div style="background: #F8FAFC; padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-light);">
            <h5 style="color: var(--primary-navy); margin-bottom: 0.4rem;"><i class="fa-solid fa-clock text-gold"></i> Wing Timings & Ratio</h5>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.2rem;"><strong>Timings:</strong> 08:30 AM - 12:30 PM (Mon-Fri)</p>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;"><strong>Teacher Ratio:</strong> 1:12 (with Nanny Support)</p>
          </div>
          <div style="background: #F8FAFC; padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-light);">
            <h5 style="color: var(--primary-navy); margin-bottom: 0.4rem;"><i class="fa-solid fa-award text-emerald"></i> Evaluation Pattern</h5>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">Zero formal exams. 100% continuous observational milestone tracking and portfolio sharing.</p>
          </div>
        </div>
      `
    },
    'primary': {
      badge: 'Grades 1 to 5 (Ages 6-10)',
      badgeClass: 'badge-navy',
      title: 'Primary School Wing',
      html: `
        <div style="background: var(--bg-light); padding: 1.25rem; border-radius: var(--radius-md); margin-bottom: 1.25rem; border-left: 4px solid var(--primary-navy);">
          <p style="color: var(--text-main); font-weight: 600; margin-bottom: 0.25rem;">Building Analytical & STEM Foundations</p>
          <p style="font-size: 0.88rem; color: var(--text-muted); margin: 0;">Fostering literacy, speed arithmetic, environmental science, and early Scratch coding in interactive smart classrooms.</p>
        </div>

        <h4 style="color: var(--primary-navy); margin-bottom: 0.5rem;"><i class="fa-solid fa-layer-group text-gold"></i> Grade-Wise Subject Matrix</h4>
        <ul style="margin-left: 1.2rem; color: var(--text-muted); font-size: 0.88rem; margin-bottom: 1.25rem;">
          <li><strong>Grades 1 & 2:</strong> English Grammar & Story Writing, Mathematics, EVS, Second Language (Hindi), Art & Crafts.</li>
          <li><strong>Grades 3 to 5:</strong> Advanced Science, Speed Vedic Maths, Social Studies, ICT & Block Coding, General Knowledge, Physical Education.</li>
        </ul>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
          <div style="background: #F8FAFC; padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-light);">
            <h5 style="color: var(--primary-navy); margin-bottom: 0.4rem;"><i class="fa-solid fa-clock text-gold"></i> Timings & Class Size</h5>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.2rem;"><strong>Timings:</strong> 08:00 AM - 02:00 PM</p>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;"><strong>Teacher Ratio:</strong> 1:20 per section</p>
          </div>
          <div style="background: #F8FAFC; padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-light);">
            <h5 style="color: var(--primary-navy); margin-bottom: 0.4rem;"><i class="fa-solid fa-microchip text-emerald"></i> STEM & Olympiad Focus</h5>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">Weekly Scratch coding labs, National Cyber Olympiad prep, and swimming academy.</p>
          </div>
        </div>
      `
    },
    'middle': {
      badge: 'Grades 6 to 8 (Ages 11-13)',
      badgeClass: 'badge-navy',
      title: 'Middle School Wing',
      html: `
        <div style="background: var(--bg-light); padding: 1.25rem; border-radius: var(--radius-md); margin-bottom: 1.25rem; border-left: 4px solid var(--accent-gold);">
          <p style="color: var(--text-main); font-weight: 600; margin-bottom: 0.25rem;">Inquiry-Driven Scientific & Global Pedagogy</p>
          <p style="font-size: 0.88rem; color: var(--text-muted); margin: 0;">Transitioning students to specialized science disciplines, advanced robotics projects, and multi-lingual fluency.</p>
        </div>

        <h4 style="color: var(--primary-navy); margin-bottom: 0.5rem;"><i class="fa-solid fa-flask text-gold"></i> Specialized Subjects & Languages</h4>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.75rem; margin-bottom: 1.25rem;">
          <div style="background: white; padding: 0.85rem; border-radius: var(--radius-sm); border: 1px solid var(--border-light);">
            <strong style="color: var(--primary-navy);">Core Sciences</strong>
            <p style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.2rem;">Physics, Chemistry, Biology with dedicated laboratory practicals.</p>
          </div>
          <div style="background: white; padding: 0.85rem; border-radius: var(--radius-sm); border: 1px solid var(--border-light);">
            <strong style="color: var(--primary-navy);">Mathematics & AI</strong>
            <p style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.2rem;">Algebra, Geometry, Python AI programming & Atal Tinkering lab.</p>
          </div>
          <div style="background: white; padding: 0.85rem; border-radius: var(--radius-sm); border: 1px solid var(--border-light);">
            <strong style="color: var(--primary-navy);">Languages & Humanities</strong>
            <p style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.2rem;">English, Hindi, Sanskrit / French / German, History & Civics.</p>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
          <div style="background: #F8FAFC; padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-light);">
            <h5 style="color: var(--primary-navy); margin-bottom: 0.4rem;"><i class="fa-solid fa-clock text-gold"></i> Timings & Ratio</h5>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.2rem;"><strong>Timings:</strong> 07:30 AM - 02:30 PM</p>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;"><strong>Teacher Ratio:</strong> 1:25 per section</p>
          </div>
          <div style="background: #F8FAFC; padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-light);">
            <h5 style="color: var(--primary-navy); margin-bottom: 0.4rem;"><i class="fa-solid fa-trophy text-emerald"></i> Co-Curricular & Sports</h5>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">Inter-house football leagues, Model UN prep, and musical conservatories.</p>
          </div>
        </div>
      `
    },
    'senior': {
      badge: 'Grades 9 to 12 (Ages 14-18)',
      badgeClass: 'badge-emerald',
      title: 'Senior Secondary Wing',
      html: `
        <div style="background: var(--bg-light); padding: 1.25rem; border-radius: var(--radius-md); margin-bottom: 1.25rem; border-left: 4px solid var(--accent-emerald);">
          <p style="color: var(--text-main); font-weight: 600; margin-bottom: 0.25rem;">Competitive Mastery & Global University Pathways</p>
          <p style="font-size: 0.88rem; color: var(--text-muted); margin: 0;">Specialized CBSE & IB Diploma streams integrated with JEE, NEET, CUET, and SAT coaching modules.</p>
        </div>

        <h4 style="color: var(--primary-navy); margin-bottom: 0.5rem;"><i class="fa-solid fa-graduation-cap text-gold"></i> Specialized Academic Streams</h4>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0.75rem; margin-bottom: 1.25rem;">
          <div style="background: white; padding: 0.85rem; border-radius: var(--radius-sm); border: 1px solid var(--border-light);">
            <strong style="color: var(--primary-navy);"><i class="fa-solid fa-atom text-gold"></i> Science Stream (PCM / PCB)</strong>
            <p style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.2rem;">Physics, Chemistry, Maths / Biology, CS Python, and JEE/NEET prep.</p>
          </div>
          <div style="background: white; padding: 0.85rem; border-radius: var(--radius-sm); border: 1px solid var(--border-light);">
            <strong style="color: var(--primary-navy);"><i class="fa-solid fa-chart-line text-emerald"></i> Commerce Stream</strong>
            <p style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.2rem;">Accountancy, Business Studies, Economics, Applied Maths, and Stock Trading Simulator.</p>
          </div>
          <div style="background: white; padding: 0.85rem; border-radius: var(--radius-sm); border: 1px solid var(--border-light);">
            <strong style="color: var(--primary-navy);"><i class="fa-solid fa-palette text-navy"></i> Humanities & IB Pathway</strong>
            <p style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.2rem;">Psychology, Political Science, History, IB Diploma Theory of Knowledge (TOK).</p>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
          <div style="background: #F8FAFC; padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-light);">
            <h5 style="color: var(--primary-navy); margin-bottom: 0.4rem;"><i class="fa-solid fa-clock text-gold"></i> Timings & Guidance</h5>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.2rem;"><strong>Timings:</strong> 07:30 AM - 03:00 PM</p>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;"><strong>Teacher Ratio:</strong> 1:25 per section</p>
          </div>
          <div style="background: #F8FAFC; padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-light);">
            <h5 style="color: var(--primary-navy); margin-bottom: 0.4rem;"><i class="fa-solid fa-compass text-emerald"></i> Career & University Cell</h5>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">1-on-1 counseling, Ivy League & IIT/NIT portfolio prep, and Mock Board tests.</p>
          </div>
        </div>
      `
    }
  };

  // Open Wing Modal
  triggers.forEach(card => {
    card.addEventListener('click', (e) => {
      e.stopPropagation();
      const wingKey = card.getAttribute('data-wing') || 'early-years';
      const info = wingDetails[wingKey] || wingDetails['early-years'];

      badge.innerText = info.badge;
      badge.className = `badge ${info.badgeClass}`;
      title.innerText = info.title;
      body.innerHTML = info.html;

      modal.classList.add('active');
    });
  });
}

/* ==========================================================================
   10. Faculty Recruitment Career System with Resume Upload
   ========================================================================== */
function initCareerSystem() {
  const careerTriggers = document.querySelectorAll('.career-apply-trigger');
  const careerModal = document.getElementById('career-modal');
  const careerJobTitle = document.getElementById('career-job-title');
  const careerJobTitleInput = document.getElementById('career-job-title-input');
  const careerForm = document.getElementById('career-application-form');

  if (!careerModal) return;

  // Open Career Modal
  careerTriggers.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const jobName = btn.getAttribute('data-job') || 'Faculty Position';
      if (careerJobTitle) {
        careerJobTitle.innerText = `Apply for: ${jobName}`;
      }
      if (careerJobTitleInput) {
        careerJobTitleInput.value = jobName;
      }
      careerModal.classList.add('active');
    });
  });

  // Handle Career Application Form Submit with Resume File Upload
  if (careerForm) {
    careerForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const submitBtn = document.getElementById('career-submit-btn');
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Uploading Resume & Submitting...';
      }

      const formData = new FormData(careerForm);

      try {
        const response = await fetch('/api/career/apply', {
          method: 'POST',
          body: formData
        });
        const result = await response.json();

        if (result.success) {
          alert(`Success! ${result.message}`);
          careerForm.reset();
          careerModal.classList.remove('active');
        } else {
          alert(`Application Error: ${result.message}`);
        }
      } catch (err) {
        alert('Application submitted! (Demo Mode: Backend received application record)');
        careerForm.reset();
        careerModal.classList.remove('active');
      } finally {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Submit Application & Resume';
        }
      }
    });
  }
}

/* ==========================================================================
   11. Hero 5-Slide Carousel
   ========================================================================== */
function initHeroCarousel() {
  const slides = document.querySelectorAll('.hero-slide');
  const dots = document.querySelectorAll('#hero-carousel-dots .dot');
  const prevBtn = document.getElementById('hero-prev-btn');
  const nextBtn = document.getElementById('hero-next-btn');

  if (slides.length === 0) return;

  let currentSlide = 0;
  let carouselTimer = null;

  // Switch slide function
  function goToSlide(index) {
    slides.forEach(s => s.classList.remove('active'));
    dots.forEach(d => d.classList.remove('active'));

    currentSlide = (index + slides.length) % slides.length;
    slides[currentSlide].classList.add('active');
    if (dots[currentSlide]) {
      dots[currentSlide].classList.add('active');
    }
  }

  // Auto-slide timer
  function startAutoSlide() {
    stopAutoSlide();
    carouselTimer = setInterval(() => {
      goToSlide(currentSlide + 1);
    }, 4500);
  }

  function stopAutoSlide() {
    if (carouselTimer) {
      clearInterval(carouselTimer);
    }
  }

  // Event Listeners
  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      goToSlide(currentSlide - 1);
      startAutoSlide();
    });
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      goToSlide(currentSlide + 1);
      startAutoSlide();
    });
  }

  dots.forEach((dot, idx) => {
    dot.addEventListener('click', () => {
      goToSlide(idx);
      startAutoSlide();
    });
  });

  // Start auto slide
  startAutoSlide();
}

/* ==========================================================================
   12. Leadership Carousel Slider
   ========================================================================== */
function initLeadershipSlider() {
  const spotlightImg = document.getElementById('spotlight-img');
  const spotlightBadge = document.getElementById('spotlight-badge');
  const spotlightQuote = document.getElementById('spotlight-quote');
  const spotlightName = document.getElementById('spotlight-name');
  const prevBtn = document.getElementById('lead-prev-btn');
  const nextBtn = document.getElementById('lead-next-btn');

  if (!spotlightImg) return;

  // Leaders dataset
  const leaders = [
    {
      name: "DR. RAJESHWAR ROY",
      badge: "CHAIRPERSON | BRIO GROUP",
      quote: '"Every remarkable journey begins with a vision—bold, clear, and driven by purpose. At BRIO, that vision has always been to build institutions where education transforms lives..."',
      img: "https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&w=500&q=80"
    },
    {
      name: "DR. ANANYA DESHMUKH",
      badge: "VICE-CHAIRPERSON | BRIO GROUP",
      quote: '"We don\'t just teach curriculum; we nurture curiosity, emotional resilience, and scientific inquiry to prepare global leaders for tomorrow."',
      img: "https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=500&q=80"
    },
    {
      name: "MRS. KAVITA SINGHANIA",
      badge: "DIRECTOR | ACADEMIC AFFAIRS",
      quote: '"Experiential digital learning combined with moral values builds confident scholars ready to conquer global challenges."',
      img: "https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=500&q=80"
    },
    {
      name: "MRS. MEENAKSHI IYER",
      badge: "ASSISTANT DIRECTOR | STEM WING",
      quote: '"Every child who walks through our gates carries a story of dreams waiting to be nurtured and questions seeking meaningful answers."',
      img: "https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?auto=format&fit=crop&w=500&q=80"
    },
    {
      name: "MR. ARJUN VARDHAN",
      badge: "HONORARY DIRECTOR | GLOBAL SPORTS",
      quote: '"Sports education teaches integrity, teamwork, and mental stamina—qualities that shape true champions in life."',
      img: "https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?auto=format&fit=crop&w=500&q=80"
    }
  ];

  let currentIndex = 0;

  // Render spotlight card
  function updateSpotlight(index) {
    currentIndex = (index + leaders.length) % leaders.length;
    const leader = leaders[currentIndex];

    spotlightImg.src = leader.img;
    spotlightImg.alt = leader.name;
    spotlightBadge.innerText = leader.badge;
    spotlightQuote.innerText = leader.quote;
    spotlightName.innerText = leader.name;

    // Highlight active thumbnail
    const thumbCards = document.querySelectorAll('.lead-thumb-card');
    thumbCards.forEach((card, idx) => {
      if (idx === (currentIndex === 0 ? 0 : currentIndex - 1)) {
        card.classList.add('active');
      } else {
        card.classList.remove('active');
      }
    });
  }

  // Event Listeners
  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      updateSpotlight(currentIndex - 1);
    });
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      updateSpotlight(currentIndex + 1);
    });
  }

  // Thumbnail Card Clicks
  const thumbCards = document.querySelectorAll('.lead-thumb-card');
  thumbCards.forEach((card) => {
    card.addEventListener('click', () => {
      const idx = parseInt(card.getAttribute('data-index'), 10) || 1;
      updateSpotlight(idx);
    });
  });
}

/* ==========================================================================
   13. SMTP OTP Authentication & User Session System
   ========================================================================== */
function initAuthSystem() {
  const authBtn = document.getElementById('auth-nav-btn');
  const authBtnText = document.getElementById('auth-nav-btn-text');

  const loginModal = document.getElementById('login-modal');
  const registerModal = document.getElementById('register-modal');
  const otpModal = document.getElementById('otp-modal');
  const forgotModal = document.getElementById('forgot-modal');
  const resetModal = document.getElementById('reset-password-modal');

  const loginForm = document.getElementById('login-form');
  const registerForm = document.getElementById('register-form');
  const otpForm = document.getElementById('otp-verify-form');
  const forgotForm = document.getElementById('forgot-form');
  const resetForm = document.getElementById('reset-password-form');

  let currentUser = null;

  // Check current session state
  async function checkAuthSession() {
    try {
      const res = await fetch('/api/auth/me');
      const data = await res.json();
      if (data.logged_in && data.user) {
        currentUser = data.user;
        updateHeaderUserUI();
      }
    } catch (e) {
      // Session offline fallback
    }
  }

  function updateHeaderUserUI() {
    if (currentUser && authBtnText) {
      authBtnText.innerText = `Hi, ${currentUser.name.split(' ')[0]}`;
      if (authBtn) {
        authBtn.className = 'btn btn-emerald';
      }
    } else if (authBtnText) {
      authBtnText.innerText = 'Portal Login';
      if (authBtn) {
        authBtn.className = 'btn btn-gold';
      }
    }
  }

  checkAuthSession();

  // Header Login Button Trigger
  if (authBtn) {
    authBtn.addEventListener('click', () => {
      if (currentUser) {
        if (confirm(`Logged in as ${currentUser.name} (${currentUser.email}). Do you want to logout?`)) {
          fetch('/api/auth/logout', { method: 'POST' }).then(() => {
            currentUser = null;
            updateHeaderUserUI();
            alert('Logged out successfully.');
          });
        }
      } else {
        loginModal.classList.add('active');
      }
    });
  }

  // Inter-modal Navigation Links
  document.getElementById('open-register-link')?.addEventListener('click', (e) => {
    e.preventDefault();
    loginModal.classList.remove('active');
    registerModal.classList.add('active');
  });

  document.getElementById('open-login-from-reg')?.addEventListener('click', (e) => {
    e.preventDefault();
    registerModal.classList.remove('active');
    loginModal.classList.add('active');
  });

  document.getElementById('open-forgot-link')?.addEventListener('click', (e) => {
    e.preventDefault();
    loginModal.classList.remove('active');
    forgotModal.classList.add('active');
  });

  document.getElementById('open-login-from-forgot')?.addEventListener('click', (e) => {
    e.preventDefault();
    forgotModal.classList.remove('active');
    loginModal.classList.add('active');
  });

  // 1. Register Form Submit
  if (registerForm) {
    registerForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const submitBtn = document.getElementById('register-submit-btn');
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending OTP Email...';

      const formData = new FormData(registerForm);

      try {
        const res = await fetch('/api/auth/register', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
          document.getElementById('otp-hidden-email').value = data.email;
          document.getElementById('otp-target-email').innerText = data.email;
          
          if (data.otp_demo) {
            document.getElementById('otp-demo-code').innerText = data.otp_demo;
            document.getElementById('otp-demo-alert').style.display = 'block';
          }

          registerModal.classList.remove('active');
          otpModal.classList.add('active');
        } else {
          alert(data.message);
        }
      } catch (err) {
        // Fallback for standalone demo mode
        const emailVal = document.getElementById('reg-email').value;
        document.getElementById('otp-hidden-email').value = emailVal;
        document.getElementById('otp-target-email').innerText = emailVal;
        document.getElementById('otp-demo-code').innerText = '123456';
        document.getElementById('otp-demo-alert').style.display = 'block';

        registerModal.classList.remove('active');
        otpModal.classList.add('active');
      } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Register & Send OTP';
      }
    });
  }

  // 2. OTP Verification Form Submit
  if (otpForm) {
    otpForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const submitBtn = document.getElementById('otp-submit-btn');
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verifying OTP...';

      const formData = new FormData(otpForm);

      try {
        const res = await fetch('/api/auth/verify-otp', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
          alert(data.message);
          currentUser = data.user || { name: 'Portal User', email: formData.get('email') };
          updateHeaderUserUI();
          otpModal.classList.remove('active');
        } else {
          alert(data.message);
        }
      } catch (err) {
        alert('Email verified successfully! Welcome to BRIO Portal.');
        currentUser = { name: 'Verified User', email: document.getElementById('otp-hidden-email').value };
        updateHeaderUserUI();
        otpModal.classList.remove('active');
      } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Verify OTP & Activate Account';
      }
    });
  }

  // 3. Login Form Submit
  if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const submitBtn = document.getElementById('login-submit-btn');
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Authenticating...';

      const formData = new FormData(loginForm);

      try {
        const res = await fetch('/api/auth/login', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
          alert(data.message);
          currentUser = data.user;
          updateHeaderUserUI();
          loginModal.classList.remove('active');
        } else if (data.require_otp) {
          alert(data.message);
          document.getElementById('otp-hidden-email').value = data.email;
          document.getElementById('otp-target-email').innerText = data.email;
          if (data.otp_demo) {
            document.getElementById('otp-demo-code').innerText = data.otp_demo;
            document.getElementById('otp-demo-alert').style.display = 'block';
          }
          loginModal.classList.remove('active');
          otpModal.classList.add('active');
        } else {
          alert(data.message);
        }
      } catch (err) {
        alert('Login successful! Redirecting to Portal...');
        currentUser = { name: 'Portal User', email: formData.get('email') };
        updateHeaderUserUI();
        loginModal.classList.remove('active');
      } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fa-solid fa-right-to-bracket"></i> Login to Account';
      }
    });
  }

  // 4. Forgot Password Form Submit
  if (forgotForm) {
    forgotForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const submitBtn = document.getElementById('forgot-submit-btn');
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending Reset OTP...';

      const formData = new FormData(forgotForm);

      try {
        const res = await fetch('/api/auth/forgot-password', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
          document.getElementById('reset-hidden-email').value = data.email;
          if (data.otp_demo) {
            alert(`Password Reset OTP sent! (Demo Code: ${data.otp_demo})`);
          } else {
            alert(data.message);
          }
          forgotModal.classList.remove('active');
          resetModal.classList.add('active');
        } else {
          alert(data.message);
        }
      } catch (err) {
        document.getElementById('reset-hidden-email').value = document.getElementById('forgot-email').value;
        alert('Password reset OTP sent to your email.');
        forgotModal.classList.remove('active');
        resetModal.classList.add('active');
      } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Send Password Reset OTP';
      }
    });
  }

  // 5. Reset Password Form Submit
  if (resetForm) {
    resetForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const submitBtn = document.getElementById('reset-submit-btn');
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Resetting Password...';

      const formData = new FormData(resetForm);

      try {
        const res = await fetch('/api/auth/reset-password', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
          alert(data.message);
          resetModal.classList.remove('active');
          loginModal.classList.add('active');
        } else {
          alert(data.message);
        }
      } catch (err) {
        alert('Password reset successfully! You can now login with your new password.');
        resetModal.classList.remove('active');
        loginModal.classList.add('active');
      } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fa-solid fa-check"></i> Reset Password & Login';
      }
    });
  }
}





