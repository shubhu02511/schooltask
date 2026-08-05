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
  
  // Initialize Portal Simulator
  initPortalSimulator();
  
  // Initialize Remote Control Panel
  initAdmissionRemoteControl();

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
    // Reset page active state
    pageViews.forEach(page => page.classList.remove('active-page'));
    
    // Find target page
    const targetPage = document.getElementById(targetId) || document.getElementById('home');
    targetPage.classList.add('active-page');

    // Update Nav Active State
    navLinks.forEach(link => {
      if (link.getAttribute('href') === `#${targetId}`) {
        link.classList.add('active');
      } else {
        link.classList.remove('active');
      }
    });

    // Scroll to top smooth
    window.scrollTo({ top: 0, behavior: 'smooth' });
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

/* ==========================================================================
   6. Parent & Student Portal
   ========================================================================== */
function initPortalSimulator() {
  const payFeeBtn = document.getElementById('portal-pay-btn');
  
  if (payFeeBtn) {
    payFeeBtn.addEventListener('click', (e) => {
      e.preventDefault();
      const amount = document.getElementById('portal-pay-amount').value || '₹42,500';
      const term = document.getElementById('portal-pay-term').value || 'Term 2 Tuition';

      const txnId = 'TXN-' + Math.floor(10000000 + Math.random() * 90000000);
      
      // Update Receipt Modal
      document.getElementById('receipt-txn-id').innerText = txnId;
      document.getElementById('receipt-amount').innerText = amount;
      document.getElementById('receipt-term').innerText = term;
      document.getElementById('receipt-date').innerText = new Date().toLocaleDateString('en-IN', {
        year: 'numeric', month: 'short', day: 'numeric'
      });

      document.getElementById('portal-receipt-modal').classList.add('active');
    });
  }

  // Modal Close Listeners
  document.querySelectorAll('.modal-close-trigger').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.modal-backdrop').forEach(m => m.classList.remove('active'));
    });
  });
}

/* ==========================================================================
   7. Header Admission Remote Control System
   ========================================================================== */
function initAdmissionRemoteControl() {
  const triggerBtn = document.getElementById('admin-remote-trigger-btn');
  const remoteModal = document.getElementById('admin-remote-modal');

  const btnOpen = document.getElementById('remote-btn-open');
  const btnWaitlist = document.getElementById('remote-btn-waitlist');
  const btnClosed = document.getElementById('remote-btn-closed');

  const inputBtnText = document.getElementById('remote-input-btn-text');
  const inputTickerMsg = document.getElementById('remote-input-ticker-msg');
  const applyLiveBtn = document.getElementById('remote-apply-live-btn');
  const resetBtn = document.getElementById('remote-reset-default-btn');

  const headerApplyBtn = document.getElementById('header-apply-btn');
  const headerApplyText = document.getElementById('header-apply-btn-text');
  const headerApplyIcon = document.getElementById('header-apply-icon');
  const tickerStatusText = document.getElementById('ticker-status-text');
  const tickerMsgText = document.getElementById('ticker-message-text');

  if (!triggerBtn || !remoteModal) return;

  let currentMode = 'open';

  // Open Remote Modal
  triggerBtn.addEventListener('click', () => {
    remoteModal.classList.add('active');
  });

  // Switch Mode Handler
  function selectMode(mode) {
    currentMode = mode;
    btnOpen.className = 'remote-status-btn' + (mode === 'open' ? ' active-status-open' : '');
    btnWaitlist.className = 'remote-status-btn' + (mode === 'waitlist' ? ' active-status-waitlist' : '');
    btnClosed.className = 'remote-status-btn' + (mode === 'closed' ? ' active-status-closed' : '');

    if (mode === 'open') {
      inputBtnText.value = 'Apply 2026-27';
      inputTickerMsg.value = 'Entrance Assessment Registration for Pre-K to Grade 11 is now live!';
    } else if (mode === 'waitlist') {
      inputBtnText.value = 'Waitlist 2026-27';
      inputTickerMsg.value = 'Regular seats full. Applications currently accepted for Waitlist Pool.';
    } else if (mode === 'closed') {
      inputBtnText.value = 'Admissions Closed';
      inputTickerMsg.value = 'Admissions for Academic Year 2026-27 are currently closed.';
    }
  }

  btnOpen.addEventListener('click', () => selectMode('open'));
  btnWaitlist.addEventListener('click', () => selectMode('waitlist'));
  btnClosed.addEventListener('click', () => selectMode('closed'));

  // Apply Settings to Header Live
  function transmitLiveSettings() {
    const customText = inputBtnText.value.trim() || 'Apply 2026-27';
    const customTicker = inputTickerMsg.value.trim() || 'Entrance Assessment Registration is live!';

    headerApplyText.innerText = customText;
    if (tickerMsgText) tickerMsgText.innerText = customTicker;

    if (currentMode === 'open') {
      tickerStatusText.innerText = 'ADMISSIONS OPEN 2026-27';
      headerApplyBtn.style.background = 'linear-gradient(135deg, var(--accent-gold), var(--accent-gold-hover))';
      headerApplyBtn.style.color = '#0F172A';
      headerApplyIcon.className = 'fa-solid fa-file-pen';
      headerApplyBtn.setAttribute('href', '#admissions');
    } else if (currentMode === 'waitlist') {
      tickerStatusText.innerText = 'ADMISSIONS WAITLIST ONLY';
      headerApplyBtn.style.background = 'linear-gradient(135deg, #F59E0B, #D97706)';
      headerApplyBtn.style.color = '#0F172A';
      headerApplyIcon.className = 'fa-solid fa-clock';
      headerApplyBtn.setAttribute('href', '#admissions');
    } else if (currentMode === 'closed') {
      tickerStatusText.innerText = 'ADMISSIONS CLOSED';
      headerApplyBtn.style.background = '#DC2626';
      headerApplyBtn.style.color = 'white';
      headerApplyIcon.className = 'fa-solid fa-lock';
      headerApplyBtn.setAttribute('href', '#');
    }

    remoteModal.classList.remove('active');
  }

  // Closed button alert handler
  if (headerApplyBtn) {
    headerApplyBtn.addEventListener('click', (e) => {
      if (currentMode === 'closed') {
        e.preventDefault();
        alert('📢 NOTICE: Admissions for Academic Session 2026-27 are currently CLOSED. Please check back later or contact Admissions Desk at +91 (0542) 2890-400.');
      }
    });
  }

  if (applyLiveBtn) {
    applyLiveBtn.addEventListener('click', transmitLiveSettings);
  }

  if (resetBtn) {
    resetBtn.addEventListener('click', () => {
      selectMode('open');
      transmitLiveSettings();
    });
  }
}
