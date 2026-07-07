<?php
// Public Landing Page - Islamabad Rehab Center (IRC)
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/db.php';
require_once __DIR__ . '/core/functions.php';

// Fetch system settings for branding
$system_name = "Islamabad Rehab Center";
$system_logo = "https://cdn-icons-png.flaticon.com/512/3063/3063176.png";
$footer_text = "© 2026 Islamabad Rehab Center. All rights reserved.";

try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
    while ($row = $stmt->fetch()) {
        if ($row['setting_key'] === 'system_name') $system_name = $row['setting_value'];
        if ($row['setting_key'] === 'system_logo') $system_logo = $row['setting_value'];
        if ($row['setting_key'] === 'footer_text') $footer_text = $row['setting_value'];
    }
    if (!empty($system_logo) && strpos($system_logo, 'http') !== 0) {
        $system_logo = BASE_URL . ltrim($system_logo, '/\\');
    }
} catch (Exception $e) {
    // Fail silently
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo sanitize($system_name); ?> | Islamabad Rehab Center</title>
    <!-- Google Fonts: Outfit & Inter -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Outfit:300,400,600,700|Inter:300,400,500,600&display=swap">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Custom Theme CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/custom.css">
    
    <style>
        .navbar-brand img {
            max-height: 45px;
            width: auto;
        }
        .hero-banner {
            background: linear-gradient(135deg, #FF8A00 0%, #E05300 50%, #9E1B00 100%);
            color: white;
            padding: 140px 0 120px;
            border-bottom-left-radius: 60px;
            border-bottom-right-radius: 60px;
            position: relative;
            overflow: hidden;
        }
        .hero-banner::before {
            content: '';
            position: absolute;
            top: 0; right: 0; bottom: 0; left: 0;
            background: radial-gradient(circle at top right, rgba(255,255,255,0.15) 0%, transparent 60%);
            pointer-events: none;
        }
        .stat-card {
            border: 1px solid rgba(255, 122, 0, 0.15);
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 10px 30px rgba(255, 122, 0, 0.05);
            border-radius: 20px;
            transition: var(--irc-transition);
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(255, 122, 0, 0.12);
        }
        .faq-item .accordion-button:not(.collapsed) {
            background-color: rgba(255, 122, 0, 0.1);
            color: var(--irc-primary);
            font-weight: 600;
        }
        .testimonial-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid rgba(0,0,0,0.05);
            padding: 30px;
            box-shadow: var(--irc-shadow);
            transition: var(--irc-transition);
        }
        .testimonial-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--irc-shadow-lg);
        }
    </style>
</head>
<body class="bg-light">

    <!-- Top Sticky Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center text-primary fw-bold" href="#">
                <img src="<?php echo sanitize($system_logo); ?>" alt="Logo" class="me-2 rounded bg-light p-1">
                <span class="fs-4"><?php echo sanitize($system_name); ?></span>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center gap-2">
                    <li class="nav-item"><a class="nav-link px-3" href="#programs">Programs</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="#metrics">Facility Stats</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="#portals">Portal Gateways</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="#faq">FAQ</a></li>
                    <li class="nav-item ms-lg-3">
                        <a href="login.php" class="btn btn-primary px-4 py-2 rounded-pill shadow">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Staff & Patient Login
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-banner text-center text-lg-start position-relative">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-7 animate-fade-in">
                    <span class="badge bg-white text-primary px-3 py-2 rounded-pill mb-3 text-uppercase fw-semibold shadow-sm">
                        <i class="bi bi-shield-heart-fill me-1"></i> Compassionate Clinical Recovery
                    </span>
                    <h1 class="display-3 fw-bold mb-4 text-white leading-tight">
                        Empowering Recovery, Restoring Purpose & Rebuilding Families
                    </h1>
                    <p class="lead mb-5 opacity-90 fs-5 text-white-50">
                        Islamabad's premier substance rehabilitation and psychological therapy institute. Our evidence-based clinical programs combine medical detox, therapy, and family integration to guide sustainable rehabilitation.
                    </p>
                    <div class="d-flex flex-wrap justify-content-center justify-content-lg-start gap-3">
                        <a href="#portals" class="btn btn-light text-primary btn-lg px-5 py-3 rounded-pill fw-semibold shadow">
                            Access Portal Gateways <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                        <a href="#programs" class="btn btn-outline-light btn-lg px-5 py-3 rounded-pill fw-semibold">
                            Explore Programs
                        </a>
                    </div>
                </div>
                <div class="col-lg-5 text-center position-relative">
                    <img src="https://images.unsplash.com/photo-1576091160550-2173dba999ef?auto=format&fit=crop&w=800&q=80" alt="Rehab Center Care" class="img-fluid rounded-4 shadow-lg border border-white border-4 animate-fade-in" style="animation-delay: 0.2s;">
                </div>
            </div>
        </div>
    </section>

    <!-- Programs & Services Section -->
    <section class="py-5 my-5" id="programs">
        <div class="container py-4">
            <div class="text-center mb-5 max-width-md mx-auto">
                <span class="text-primary fw-semibold text-uppercase">Rehabilitation Paths</span>
                <h2 class="h1 fw-bold mt-2">Specialized Recovery Operations</h2>
                <p class="text-secondary col-md-8 mx-auto mt-3">
                    We deliver customized care paths designed to stabilize, build resilience, and establish a strong psychological framework for reintegration.
                </p>
            </div>
            
            <div class="row g-4">
                <!-- Detox -->
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 p-4 text-center border-0 shadow-sm glass-panel">
                        <div class="fs-1 text-primary mb-3"><i class="bi bi-prescription2"></i></div>
                        <h4 class="mb-3">Clinical Detox</h4>
                        <p class="text-secondary small">
                            Safe, medically supervised withdrawal management overseen by experienced clinicians and psychologists.
                        </p>
                    </div>
                </div>
                <!-- Residential Rehab -->
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 p-4 text-center border-0 shadow-sm glass-panel">
                        <div class="fs-1 text-primary mb-3"><i class="bi bi-house-heart"></i></div>
                        <h4 class="mb-3">Residential Rehab</h4>
                        <p class="text-secondary small">
                            Full-time residential therapy including intensive CBT, DBT, and community support networks.
                        </p>
                    </div>
                </div>
                <!-- Cognitive Therapy -->
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 p-4 text-center border-0 shadow-sm glass-panel">
                        <div class="fs-1 text-primary mb-3"><i class="bi bi-chat-left-quote"></i></div>
                        <h4 class="mb-3">Psychotherapy</h4>
                        <p class="text-secondary small">
                            One-on-one sessions and support groups targeted at resolving mental health trauma and anxiety triggers.
                        </p>
                    </div>
                </div>
                <!-- Aftercare Support -->
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 p-4 text-center border-0 shadow-sm glass-panel">
                        <div class="fs-1 text-primary mb-3"><i class="bi bi-shield-check"></i></div>
                        <h4 class="mb-3">Aftercare Support</h4>
                        <p class="text-secondary small">
                            Outpatient check-ups, regular tracking, and family counseling to sustain physical and psychological health.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Clinic Statistics Section -->
    <section class="py-5 bg-white border-top border-bottom" id="metrics">
        <div class="container py-4">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <span class="text-primary fw-semibold text-uppercase">Facility Standards</span>
                    <h2 class="h1 fw-bold mt-2">Empirical Recovery Metrics</h2>
                    <p class="text-secondary mt-3">
                        Islamabad Rehab Center operates on rigorous clinical protocols. We track quantitative status markers and treatment outcomes to continuously refine and optimize our recovery frameworks.
                    </p>
                    <div class="row g-4 mt-3">
                        <div class="col-6">
                            <div class="p-3 border-start border-primary border-4 bg-light rounded">
                                <h3 class="fw-bold text-primary mb-0">150+</h3>
                                <p class="text-secondary small mb-0">Recoveries Managed</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 border-start border-primary border-4 bg-light rounded">
                                <h3 class="fw-bold text-primary mb-0">98%</h3>
                                <p class="text-secondary small mb-0">Completion Rate</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="stat-card p-4 text-center">
                                <div class="fs-2 text-primary mb-2"><i class="bi bi-people-fill"></i></div>
                                <h4>25+ Clinicians</h4>
                                <p class="text-secondary small mb-0">Doctors and clinical psychologists on standby.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="stat-card p-4 text-center">
                                <div class="fs-2 text-primary mb-2"><i class="bi bi-activity"></i></div>
                                <h4>Realtime Reports</h4>
                                <p class="text-secondary small mb-0">Transparent progress and clinical note access.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="py-5" id="testimonials">
        <div class="container py-4">
            <div class="text-center mb-5">
                <span class="text-primary fw-semibold text-uppercase">Client Reviews</span>
                <h2 class="h1 fw-bold mt-2">Stories of Reclaimed Lives</h2>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <p class="text-secondary italic mb-4">
                            "The Clinical Detox program saved my life. Dr. Sarah and the team at IRC provided constant support and clinical supervision when I needed it most."
                        </p>
                        <div class="d-flex align-items-center">
                            <div class="bg-primary text-white rounded-circle p-2 me-3"><i class="bi bi-quote"></i></div>
                            <div>
                                <h6 class="mb-0 fw-bold">Ali R.</h6>
                                <small class="text-muted">Recovered Patient (2026)</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <p class="text-secondary italic mb-4">
                            "Having complete visibility over my brother's therapy logs and assigned therapist updates kept our family assured throughout his rehabilitation journey."
                        </p>
                        <div class="d-flex align-items-center">
                            <div class="bg-primary text-white rounded-circle p-2 me-3"><i class="bi bi-quote"></i></div>
                            <div>
                                <h6 class="mb-0 fw-bold">Zainab B.</h6>
                                <small class="text-muted">Family Sponsor</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <p class="text-secondary italic mb-4">
                            "Highly structured residential program. The psychological assessment tools and counselor availability helped me rebuild distress tolerance skills."
                        </p>
                        <div class="d-flex align-items-center">
                            <div class="bg-primary text-white rounded-circle p-2 me-3"><i class="bi bi-quote"></i></div>
                            <div>
                                <h6 class="mb-0 fw-bold">Hamza M.</h6>
                                <small class="text-muted">Recovered Patient (2026)</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Portal Gateways Section -->
    <section class="py-5 bg-white border-top border-bottom" id="portals">
        <div class="container py-4">
            <div class="text-center mb-5 max-width-md mx-auto">
                <span class="text-primary fw-semibold text-uppercase">Access Gates</span>
                <h2 class="h1 fw-bold mt-2">IRC Portal Selection</h2>
                <p class="text-secondary col-md-8 mx-auto mt-3">
                    Secure access nodes for center clinicians, administrative officers, and admitted patients to check logs and run tools.
                </p>
            </div>

            <div class="row g-4 justify-content-center">
                <!-- Patient Portal -->
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow rounded-4 overflow-hidden text-center">
                        <div class="bg-primary text-white p-4">
                            <div class="fs-1 mb-2"><i class="bi bi-person-heart"></i></div>
                            <h3 class="mb-0">Patient Portal</h3>
                        </div>
                        <div class="card-body p-4">
                            <p class="text-secondary small mb-4">
                                Log mood ratings, review recovery treatment goals, check session dates, and message your clinician directly.
                            </p>
                            <a href="login.php" class="btn btn-outline-primary px-4 py-2 rounded-pill">
                                Enter Patient Portal <i class="bi bi-chevron-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Doctor / Psychologist Portal -->
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow rounded-4 overflow-hidden text-center">
                        <div class="bg-dark text-white p-4">
                            <div class="fs-1 mb-2"><i class="bi bi-heart-pulse-fill text-warning"></i></div>
                            <h3 class="mb-0">Doctor Portal</h3>
                        </div>
                        <div class="card-body p-4">
                            <p class="text-secondary small mb-4">
                                Update patient diagnoses, record clinical session notes, set up recovery goals, and respond to support messages.
                            </p>
                            <a href="login.php" class="btn btn-outline-dark px-4 py-2 rounded-pill">
                                Enter Doctor Portal <i class="bi bi-chevron-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Admin Portal -->
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow rounded-4 overflow-hidden text-center">
                        <div class="bg-secondary text-white p-4">
                            <div class="fs-1 mb-2"><i class="bi bi-shield-lock-fill"></i></div>
                            <h3 class="mb-0">Admin Portal</h3>
                        </div>
                        <div class="card-body p-4">
                            <p class="text-secondary small mb-4">
                                Manage user accounts, customize roles and permissions, configure site settings, and audit clinic telemetry logs.
                            </p>
                            <a href="login.php" class="btn btn-outline-secondary px-4 py-2 rounded-pill">
                                Enter Admin Portal <i class="bi bi-chevron-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-5" id="faq">
        <div class="container py-4">
            <div class="text-center mb-5">
                <span class="text-primary fw-semibold text-uppercase">Help & Support</span>
                <h2 class="h1 fw-bold mt-2">Frequently Asked Questions</h2>
            </div>
            
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="accordion accordion-flush glass-panel p-3" id="faqAccordion">
                        <div class="accordion-item bg-transparent faq-item border-bottom">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed bg-transparent" type="button" data-bs-toggle="collapse" data-bs-target="#faq-1">
                                    What is the average length of a residential treatment program?
                                </button>
                            </h2>
                            <div id="faq-1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body text-secondary">
                                    Our residential programs generally span between 30 to 90 days. This depends entirely on clinical evaluations made by our doctors and clinical director during intake.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item bg-transparent faq-item border-bottom">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed bg-transparent" type="button" data-bs-toggle="collapse" data-bs-target="#faq-2">
                                    How is family member involvement handled during rehabilitation?
                                </button>
                            </h2>
                            <div id="faq-2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body text-secondary">
                                    Family counseling sessions are conducted weekly. Family sponsors are also granted secure accounts to monitor therapy progress and recovery phases.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item bg-transparent faq-item border-bottom">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed bg-transparent" type="button" data-bs-toggle="collapse" data-bs-target="#faq-3">
                                    Is client clinical data secured?
                                </button>
                            </h2>
                            <div id="faq-3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body text-secondary">
                                    Yes, all patient data and clinical assessment histories are strictly secured under our Role-Based Access Control (RBAC) model. Only authorized clinicians assigned to the patient have access.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container text-center">
            <img src="<?php echo sanitize($system_logo); ?>" alt="Logo" style="max-height: 55px;" class="mb-3 rounded bg-white p-1">
            <h5 class="mb-2 text-white fw-bold"><?php echo sanitize($system_name); ?></h5>
            <p class="text-secondary small mb-4">Pathways to Physical and Behavioral Recovery</p>
            <hr class="border-secondary col-md-6 mx-auto mb-4">
            <p class="mb-0 text-muted small"><?php echo sanitize($footer_text); ?></p>
        </div>
    </footer>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
