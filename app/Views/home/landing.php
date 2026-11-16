<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1b6d39">
    <title>Msantha Investments and General Suppliers (MIGS) — Livestock & Poultry Hatchery Malawi</title>
    <link rel="icon" type="image/png" href="<?= asset('assets/img/logo.png') ?>">
    <link rel="manifest" href="<?= asset('manifest.webmanifest') ?>">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Bootstrap 5.3 & Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- App CSS -->
    <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
    <style>
        .landing-hero {
            background: linear-gradient(135deg, #0f3d1e 0%, #1b6d39 60%, #15803d 100%);
            color: #ffffff;
            position: relative;
            overflow: hidden;
        }
        .landing-hero::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: radial-gradient(circle at 80% 20%, rgba(245, 158, 11, 0.15) 0%, transparent 50%),
                        radial-gradient(circle at 20% 80%, rgba(34, 197, 94, 0.2) 0%, transparent 50%);
            pointer-events: none;
        }
        .service-card {
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            border-radius: 16px;
            background: #ffffff;
            border: 1px solid rgba(0,0,0,0.06);
        }
        .service-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 32px -8px rgba(0,0,0,0.12) !important;
            border-color: rgba(27, 109, 57, 0.3) !important;
        }
        .feature-badge {
            background-color: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            color: #ffffff;
            font-weight: 600;
        }
        .quote-box {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border-left: 5px solid #16a34a;
            border-radius: 12px;
        }
    </style>
</head>
<body class="bg-light">

    <!-- Top Announcement Bar -->
    <div class="py-2 px-3 text-white small" style="background-color: #0f3d1e;">
        <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-warning text-dark fw-bold">Est. 2006</span>
                <span>Msantha Investments and General Suppliers (MIGS) &bull; Mawira Area by Chipamba Road, Liwonde, Machinga</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <a href="tel:+265888899620" class="text-white text-decoration-none"><i class="bi bi-telephone-fill me-1 text-warning"></i>+265 8888 996 20</a>
                <span class="text-white-50">|</span>
                <a href="tel:+265999899620" class="text-white text-decoration-none"><i class="bi bi-telephone-fill me-1 text-warning"></i>+265 9998 996 20</a>
            </div>
        </div>
    </div>

    <!-- Navigation Header -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm py-2">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 text-decoration-none" href="<?= url('/') ?>">
                <img src="<?= asset('assets/img/logo.png') ?>" alt="MIGS Logo" class="app-logo" style="height: 48px; width: auto;">
                <div class="d-flex flex-column">
                    <span class="fw-bold fs-5 text-dark lh-sm">Msantha Investments</span>
                    <small class="text-muted" style="font-size: 0.72rem;">And General Suppliers (MIGS) &bull; Liwonde</small>
                </div>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navContent" aria-controls="navContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navContent">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center gap-1 gap-lg-3">
                    <li class="nav-item"><a class="nav-link text-dark fw-semibold" href="#about">About Us</a></li>
                    <li class="nav-item"><a class="nav-link text-dark fw-semibold" href="#services">Our Services</a></li>
                    <li class="nav-item"><a class="nav-link text-dark fw-semibold" href="#incubation">Egg Hatchery</a></li>
                    <li class="nav-item"><a class="nav-link text-dark fw-semibold" href="#livestock">Livestock & Breeds</a></li>
                    <li class="nav-item"><a class="nav-link text-dark fw-semibold" href="#contact">Contact</a></li>
                    <li class="nav-item ms-lg-2">
                        <a href="<?= url('/login') ?>" class="btn btn-success fw-bold px-4 py-2 rounded-pill shadow-sm d-inline-flex align-items-center gap-2">
                            <i class="bi bi-box-arrow-in-right"></i>
                            <span>Sign In to Portal</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="landing-hero py-5 py-lg-6">
        <div class="container position-relative" style="z-index: 2;">
            <div class="row align-items-center gy-5">
                <div class="col-12 col-lg-7 text-center text-lg-start">
                    <span class="badge feature-badge px-3 py-2 rounded-pill mb-3 fs-6">
                        <i class="bi bi-patch-check-fill text-warning me-1"></i>We Supply and Ear Tag for You
                    </span>
                    <h1 class="display-4 fw-extrabold mb-3 text-white lh-sm">
                        Msantha Investments & General Suppliers
                    </h1>
                    <p class="lead mb-4 text-white-50" style="font-size: 1.18rem; max-width: 620px;">
                        Specializing in high-grade livestock & poultry production, industrial egg incubation, veterinary pharmaceuticals, and farmer capacity training in Liwonde, Machinga District, Malawi.
                    </p>
                    <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-lg-start gap-3 mb-4">
                        <a href="<?= url('/login') ?>" class="btn btn-warning btn-lg fw-bold text-dark px-4 py-3 rounded-pill shadow">
                            <i class="bi bi-shield-lock-fill me-2"></i>Access Farm Portal
                        </a>
                        <a href="<?= asset('downloads/msantha-poultry-farm.apk') ?>" class="btn btn-outline-light btn-lg fw-semibold px-4 py-3 rounded-pill">
                            <i class="bi bi-android2 me-2"></i>Download Android App (APK)
                        </a>
                    </div>
                    <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-lg-start gap-4 text-white-50 small pt-2">
                        <div><i class="bi bi-check-circle-fill text-warning me-1"></i>History Since 2006</div>
                        <div><i class="bi bi-check-circle-fill text-warning me-1"></i>Commercial Incubators</div>
                        <div><i class="bi bi-check-circle-fill text-warning me-1"></i>Liwonde, Mawira &bull; Machinga</div>
                    </div>
                </div>
                <div class="col-12 col-lg-5">
                    <div class="card border-0 shadow-lg rounded-4 overflow-hidden bg-white text-dark">
                        <div class="p-4 text-white" style="background: linear-gradient(135deg, #0b2914 0%, #1b6d39 100%);">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <img src="<?= asset('assets/img/logo.png') ?>" alt="MIGS Emblem" style="height: 56px; width: auto; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">
                                <div>
                                    <span class="badge bg-warning text-dark fw-bold px-2 py-0.5 rounded-pill mb-1" style="font-size: 0.68rem;">M I G S &bull; Est. 2006</span>
                                    <h5 class="fw-bold text-white mb-0 lh-sm">Msantha Investments</h5>
                                    <small class="text-white-50" style="font-size: 0.76rem;">And General Suppliers &bull; Liwonde</small>
                                </div>
                            </div>
                            <div class="p-3 rounded-3" style="background: rgba(255,255,255,0.1); backdrop-filter: blur(4px); border-left: 3px solid #f59e0b;">
                                <p class="text-white fw-bold small mb-1 fst-italic">
                                    "WE ARE BEST AT WHAT WE DO, TRY US AND YOU WON'T REGRET"
                                </p>
                                <small class="text-warning" style="font-size: 0.75rem;">&mdash; We Supply and Ear Tag for You</small>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <h6 class="fw-bold text-dark text-uppercase small mb-3" style="letter-spacing: 0.05em;">Our Integrated Focus</h6>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <div class="p-3 bg-light rounded-3 border h-100">
                                        <div class="mb-2"><?= icon('incubator', 28) ?></div>
                                        <strong class="d-block text-dark small">Incubation Hub</strong>
                                        <span class="text-muted" style="font-size: 0.72rem;">21d / 28d Automated Cycles</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-3 bg-light rounded-3 border h-100">
                                        <div class="mb-2"><?= icon('chicken', 28) ?></div>
                                        <strong class="d-block text-dark small">Livestock & Flocks</strong>
                                        <span class="text-muted" style="font-size: 0.72rem;">6 Breeds & Ear Tagging</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-3 bg-light rounded-3 border h-100">
                                        <div class="mb-2"><?= icon('vaccine', 28) ?></div>
                                        <strong class="d-block text-dark small">Veterinary Care</strong>
                                        <span class="text-muted" style="font-size: 0.72rem;">Vaccines & Equipment</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-3 bg-light rounded-3 border h-100">
                                        <div class="mb-2"><?= icon('feed', 28) ?></div>
                                        <strong class="d-block text-dark small">Farmer Training</strong>
                                        <span class="text-muted" style="font-size: 0.72rem;">Husbandry & Consultancy</span>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                <span class="text-muted small"><i class="bi bi-geo-alt-fill text-danger me-1"></i>Liwonde, Mawira Area</span>
                                <a href="#services" class="btn btn-sm btn-outline-success fw-bold rounded-pill px-3">Explore Services &rarr;</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Key Statistics Bar -->
    <section class="py-4 bg-white border-bottom shadow-sm">
        <div class="container">
            <div class="row g-3 text-center">
                <div class="col-6 col-md-3">
                    <div class="p-2">
                        <div class="display-6 fw-bold text-success">2006</div>
                        <small class="text-muted fw-semibold text-uppercase">Year Established</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-2">
                        <div class="display-6 fw-bold text-primary">13,000+</div>
                        <small class="text-muted fw-semibold text-uppercase">Batch & Daily Records</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-2">
                        <div class="display-6 fw-bold text-warning">5 Units</div>
                        <small class="text-muted fw-semibold text-uppercase">Poultry, Bee & Transport Fleet</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-2">
                        <div class="display-6 fw-bold text-danger">100%</div>
                        <small class="text-muted fw-semibold text-uppercase">Ear Tagged & Traced</small>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Us Section -->
    <section id="about" class="py-5 py-lg-6">
        <div class="container">
            <div class="row align-items-center gy-5">
                <div class="col-12 col-lg-6">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white border-top border-4 border-success">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div class="p-2 bg-success-subtle rounded-3"><?= icon('livestock', 26) ?></div>
                                    <span class="badge bg-light text-muted border">Breeding</span>
                                </div>
                                <h6 class="fw-bold text-dark mb-1">Livestock Species</h6>
                                <p class="text-muted small mb-0">Goats, sheep, pigs, rabbits, dairy cattle, Malawi zebu cattle & high-yield poultry.</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white border-top border-4 border-warning">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div class="p-2 bg-warning-subtle rounded-3"><?= icon('incubator', 22) ?></div>
                                    <span class="badge bg-light text-muted border">Commercial</span>
                                </div>
                                <h6 class="fw-bold text-dark mb-1">Hatchery Chambers</h6>
                                <p class="text-muted small mb-0">Automated temperature and humidity egg incubation for chickens, turkeys, ducks, and guinea fowls.</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white border-top border-4 border-danger">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div class="p-2 bg-danger-subtle rounded-3"><?= icon('vaccine', 22) ?></div>
                                    <span class="badge bg-light text-muted border">Certified</span>
                                </div>
                                <h6 class="fw-bold text-dark mb-1">Ear Tagging & Health</h6>
                                <p class="text-muted small mb-0">Veterinary pharmaceuticals, prophylactic vaccines, animal identification and ear tagging.</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white border-top border-4 border-primary">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div class="p-2 bg-primary-subtle rounded-3"><?= icon('feed', 22) ?></div>
                                    <span class="badge bg-light text-muted border">Capacity</span>
                                </div>
                                <h6 class="fw-bold text-dark mb-1">Farmer Training</h6>
                                <p class="text-muted small mb-0">Husbandry workshops for smallholders, extension workers, and institutional farm managers.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill mb-2 fw-bold">About Our Company</span>
                    <h2 class="fw-bold text-dark mb-3">About Msantha Investments and General Suppliers</h2>
                    <p class="text-muted mb-3" style="font-size: 1.05rem; line-height: 1.7;">
                        This is to introduce to you <strong>Msantha Investments and General Suppliers</strong>. We are located in <strong>Liwonde, Mawira area by Chipamba road, Machinga district</strong>. We specialize in livestock and poultry production and veterinary services.
                    </p>
                    <p class="text-muted mb-4" style="font-size: 1.05rem; line-height: 1.7;">
                        <strong>Msantha Investments and General Suppliers has a history that dates back 2006.</strong> We have supplied various livestock since then to various organisations, development institutions, and commercial farms throughout Malawi.
                    </p>
                    <div class="quote-box p-4 mb-4">
                        <p class="fst-italic text-dark fw-semibold mb-2">
                            "We work as a team. Members of the organization have a reputable record of services, they set a pace for others to follow. Try us, and you will never regret."
                        </p>
                        <small class="text-success fw-bold">&mdash; Msantha Investments Leadership</small>
                    </div>
                    <div class="row g-2">
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center gap-2 p-2 bg-white rounded-3 shadow-sm border">
                                <i class="bi bi-geo-alt-fill text-danger fs-4"></i>
                                <span class="small fw-semibold text-dark">Liwonde, Machinga District</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center gap-2 p-2 bg-white rounded-3 shadow-sm border">
                                <i class="bi bi-mailbox2 text-primary fs-4"></i>
                                <span class="small fw-semibold text-dark">P.O. Box 250, Liwonde</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="py-5 bg-white border-top border-bottom">
        <div class="container">
            <div class="text-center max-w-700 mx-auto mb-5">
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-1 rounded-pill fw-bold mb-2">Comprehensive Solutions</span>
                <h2 class="fw-bold text-dark">Our Core Agricultural Services</h2>
                <p class="text-muted">Direct from our official service brochure &mdash; professional expertise across the livestock value chain.</p>
            </div>

            <div class="row g-4">
                <!-- Service 1 -->
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="service-card p-4 h-100 shadow-sm d-flex flex-column">
                        <div class="p-3 bg-success-subtle rounded-4 d-inline-flex align-items-center justify-content-center mb-3" style="width: 58px; height: 58px;">
                            <?= icon('chicken', 32) ?>
                        </div>
                        <h5 class="fw-bold text-dark mb-2">Livestock & Poultry Supply</h5>
                        <p class="text-muted small flex-grow-1">
                            Supplying diverse livestock species including goats, sheep, pigs, rabbits, graded dairy cattle, Malawi Zebu cattle, and poultry (local chickens, Mikolongwe, Kuroiler, Turkeys, Guinea fowls, Pekin ducks, and Quails).
                        </p>
                        <span class="text-success fw-bold small"><i class="bi bi-check2 me-1"></i>Certified Breeding Stock</span>
                    </div>
                </div>

                <!-- Service 2 -->
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="service-card p-4 h-100 shadow-sm d-flex flex-column">
                        <div class="p-3 bg-primary-subtle rounded-4 d-inline-flex align-items-center justify-content-center mb-3" style="width: 58px; height: 58px;">
                            <?= icon('incubator', 32) ?>
                        </div>
                        <h5 class="fw-bold text-dark mb-2">Egg Incubation Services</h5>
                        <p class="text-muted small flex-grow-1">
                            Commercial multi-stage incubation for chicken, turkey, duck, and quail eggs. Precise temperature, humidity regulation, automatic turning, Day 18 transfer candling, and official hatch certification.
                        </p>
                        <span class="text-primary fw-bold small"><i class="bi bi-check2 me-1"></i>High Fertility Hatch Rates</span>
                    </div>
                </div>

                <!-- Service 3 -->
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="service-card p-4 h-100 shadow-sm d-flex flex-column">
                        <div class="p-3 bg-warning-subtle rounded-4 d-inline-flex align-items-center justify-content-center mb-3" style="width: 58px; height: 58px;">
                            <?= icon('vaccine', 32) ?>
                        </div>
                        <h5 class="fw-bold text-dark mb-2">Veterinary Products & Ear Tags</h5>
                        <p class="text-muted small flex-grow-1">
                            "We supply and ear tag for you." Veterinary products including essential vaccines (Newcastle, Gumboro, Fowl Pox), livestock handling equipment, drinkers, feeders, and durable animal ear tags.
                        </p>
                        <span class="text-warning fw-bold small"><i class="bi bi-check2 me-1"></i>Tag-For-You Service</span>
                    </div>
                </div>

                <!-- Service 4 -->
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="service-card p-4 h-100 shadow-sm d-flex flex-column">
                        <div class="p-3 bg-info-subtle rounded-4 d-inline-flex align-items-center justify-content-center mb-3" style="width: 58px; height: 58px;">
                            <?= icon('feed', 32) ?>
                        </div>
                        <h5 class="fw-bold text-dark mb-2">Farmer & Stakeholder Training</h5>
                        <p class="text-muted small flex-grow-1">
                            Training of farmers, extension workers, and agricultural stakeholders in modern livestock production, poultry husbandry, biosecurity, and flock health management.
                        </p>
                        <span class="text-info fw-bold small"><i class="bi bi-check2 me-1"></i>Hands-On Capacity Building</span>
                    </div>
                </div>

                <!-- Service 5 -->
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="service-card p-4 h-100 shadow-sm d-flex flex-column">
                        <div class="p-3 bg-danger-subtle rounded-4 d-inline-flex align-items-center justify-content-center mb-3" style="width: 58px; height: 58px;">
                            <?= icon('ladder', 32) ?>
                        </div>
                        <h5 class="fw-bold text-dark mb-2">Consultancy Services</h5>
                        <p class="text-muted small flex-grow-1">
                            Agribusiness and livestock consulting in farm architecture, housing, disease outbreak prevention, feed formulation, and enterprise scaling for cooperatives and private farms.
                        </p>
                        <span class="text-danger fw-bold small"><i class="bi bi-check2 me-1"></i>Expert Extension Support</span>
                    </div>
                </div>

                <!-- Service 6: Digital Platform -->
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="service-card p-4 h-100 shadow-sm d-flex flex-column" style="background: linear-gradient(135deg, #14532d 0%, #1b6d39 100%); color: #ffffff;">
                        <div class="p-3 bg-white rounded-4 d-inline-flex align-items-center justify-content-center mb-3" style="width: 58px; height: 58px;">
                            <?= icon('report', 32) ?>
                        </div>
                        <h5 class="fw-bold text-white mb-2">Digital Farm Platform</h5>
                        <p class="text-white-50 small flex-grow-1">
                            Real-time flock mortality tracking, egg yield statistics, customer incubation countdowns, mobile money (PayChangu/Airtel/TNM), and offline-capable mobile PWA.
                        </p>
                        <a href="<?= url('/login') ?>" class="btn btn-warning btn-sm fw-bold rounded-pill text-dark w-100 mt-2">
                            Enter Farm System &rarr;
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Incubation Feature Deep-Dive -->
    <section id="incubation" class="py-5 py-lg-6">
        <div class="container">
            <div class="row align-items-center gy-5">
                <div class="col-12 col-lg-6">
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill mb-2 fw-bold">Hatchery Excellence</span>
                    <h2 class="fw-bold text-dark mb-3">Precision Egg Incubation for Commercial & Smallholder Poultry</h2>
                    <p class="text-muted mb-4">
                        Our Liwonde hatchery operates commercial automatic incubators with continuous digital monitoring of temperature and humidity, candling on Day 18, and hygienic hatcher baskets on Day 21.
                    </p>
                    <div class="d-flex flex-column gap-3 mb-4">
                        <div class="d-flex align-items-start gap-3">
                            <div class="p-2 bg-success text-white rounded-circle"><i class="bi bi-check-lg"></i></div>
                            <div>
                                <strong class="text-dark d-block">Species-Specific Automated Schedules</strong>
                                <span class="text-muted small">Chickens (21 days, Day 18 transfer), Ducks & Turkeys (28 days), Quails (18 days).</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <div class="p-2 bg-success text-white rounded-circle"><i class="bi bi-check-lg"></i></div>
                            <div>
                                <strong class="text-dark d-block">Automated SMS Customer Updates</strong>
                                <span class="text-muted small">SMS sent on egg intake, Day 18 transfer, and hatch day readiness for collection.</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <div class="p-2 bg-success text-white rounded-circle"><i class="bi bi-check-lg"></i></div>
                            <div>
                                <strong class="text-dark d-block">Official Hatch Certificates & Invoicing</strong>
                                <span class="text-muted small">Printable verification receipt with fertility percentage, unhatched breakdown, and balance.</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="card border-0 shadow-lg rounded-4 p-4 p-md-5 bg-white">
                        <h4 class="fw-bold text-dark mb-2"><i class="bi bi-search text-success me-2"></i>Track Your Incubation Batch</h4>
                        <p class="text-muted small mb-4">Are you a registered customer with eggs currently in our Liwonde incubators? Enter your phone number or batch code to check your status.</p>
                        
                        <form id="trackIncubationForm">
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-dark small">Customer Mobile Number</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">+265</span>
                                    <input type="tel" name="phone" id="trackPhone" class="form-control" placeholder="999 899 620" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-dark small">Batch Receipt / Job Number (Optional)</label>
                                <input type="text" name="job_ref" id="trackJobRef" class="form-control" placeholder="e.g. JOB-837 or 837">
                            </div>
                            <button type="submit" id="trackSubmitBtn" class="btn btn-success fw-bold w-100 py-2 shadow-sm mb-3">
                                <i class="bi bi-arrow-right-circle me-1"></i>Track Hatch Schedule
                            </button>
                            <small class="text-muted text-center d-block">For batch deliveries, egg bookings, or inquiries: <a href="tel:+265999899620" class="text-success fw-bold text-decoration-none">+265 9998 996 20</a></small>
                        </form>

                        <!-- Track Results Container -->
                        <div id="trackResultContainer" class="mt-3" style="display: none;"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Breeds Section -->
    <section id="livestock" class="py-5 bg-white border-top border-bottom">
        <div class="container">
            <div class="text-center max-w-700 mx-auto mb-5">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill fw-bold mb-2">Our Breeds & Livestock</span>
                <h2 class="fw-bold text-dark">Breeds Raised & Incubated at MIGS</h2>
                <p class="text-muted">High-performance dual-purpose poultry, local heritage genetics, and quality livestock suited to Malawian conditions.</p>
            </div>

            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 p-3 text-center h-100 bg-light">
                        <div class="mb-2"><?= icon('chicken', 38) ?></div>
                        <h6 class="fw-bold text-dark mb-1">Mikolongwe</h6>
                        <small class="text-muted">Resilient Malawian layer-broiler dual-purpose breed.</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 p-3 text-center h-100 bg-light">
                        <div class="mb-2"><?= icon('chicken', 38) ?></div>
                        <h6 class="fw-bold text-dark mb-1">Kuroiler</h6>
                        <small class="text-muted">Heavy egg production and fast meat weight gain.</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 p-3 text-center h-100 bg-light">
                        <div class="mb-2"><?= icon('chicken', 38) ?></div>
                        <h6 class="fw-bold text-dark mb-1">Sasso & Local</h6>
                        <small class="text-muted">High scavenger ability and authentic local taste.</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 p-3 text-center h-100 bg-light">
                        <div class="mb-2"><?= icon('turkey', 38) ?></div>
                        <h6 class="fw-bold text-dark mb-1">Poults & Turkeys</h6>
                        <small class="text-muted">28-day incubator cycles, high commercial meat value.</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 p-3 text-center h-100 bg-light">
                        <div class="mb-2"><?= icon('duck', 38) ?></div>
                        <h6 class="fw-bold text-dark mb-1">Pekin & Khaki Ducks</h6>
                        <small class="text-muted">Hardy waterfowls with prolific laying and table meat.</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 p-3 text-center h-100 bg-light">
                        <div class="mb-2"><?= icon('egg', 38) ?></div>
                        <h6 class="fw-bold text-dark mb-1">Japanese Quails</h6>
                        <small class="text-muted">Rapid 18-day incubation, high-nutrition specialty eggs.</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 p-3 text-center h-100 bg-light">
                        <div class="mb-2"><?= icon('cattle', 38) ?></div>
                        <h6 class="fw-bold text-dark mb-1">Dairy & Zebu Cattle</h6>
                        <small class="text-muted">Graded dairy cows, Malawi Zebu with ear tagging.</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 p-3 text-center h-100 bg-light">
                        <div class="mb-2"><?= icon('livestock', 38) ?></div>
                        <h6 class="fw-bold text-dark mb-1">Goats, Sheep, Pigs</h6>
                        <small class="text-muted">Boer goats, sheep, and high-farrowing pig stock.</small>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact & Map Section -->
    <section id="contact" class="py-5 py-lg-6">
        <div class="container">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden bg-white">
                <div class="row g-0">
                    <div class="col-12 col-lg-6 p-4 p-md-5 text-white" style="background: linear-gradient(135deg, #14532d 0%, #1b6d39 100%);">
                        <span class="badge bg-warning text-dark fw-bold px-3 py-1 rounded-pill mb-3">Headquarters & Hatchery</span>
                        <h3 class="fw-bold text-white mb-3">Get in Touch with MIGS</h3>
                        <p class="text-white-50 mb-4">
                            We welcome visits from farmers, agricultural organizations, and institutional buyers. Contact our Liwonde station for livestock bookings, egg delivery schedules, or training inquiries.
                        </p>
                        <div class="d-flex flex-column gap-3 mb-4">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bi bi-geo-alt-fill text-warning fs-4"></i>
                                <div>
                                    <strong class="d-block text-white">Physical Location</strong>
                                    <span class="text-white-50 small">Liwonde, Mawira Area by Chipamba Road, Machinga District, Malawi</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <i class="bi bi-mailbox2 text-warning fs-4"></i>
                                <div>
                                    <strong class="d-block text-white">Postal Address</strong>
                                    <span class="text-white-50 small">P.O. Box 250, Liwonde, Machinga</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <i class="bi bi-telephone-fill text-warning fs-4"></i>
                                <div>
                                    <strong class="d-block text-white">Direct Phone Lines</strong>
                                    <span class="text-white-50 small">+265 8888 996 20 / +265 9998 996 20</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <i class="bi bi-envelope-fill text-warning fs-4"></i>
                                <div>
                                    <strong class="d-block text-white">Email Addresses</strong>
                                    <span class="text-white-50 small">icchipeta@gmail.com &bull; icchipeta@yahoo.co.uk</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6 p-4 p-md-5 bg-white d-flex flex-column justify-content-center">
                        <h4 class="fw-bold text-dark mb-2">Send an Inquiry</h4>
                        <p class="text-muted small mb-4">Fill out your message and our Liwonde management team will respond promptly.</p>
                        
                        <form id="contactInquiryForm">
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-dark small">Your Name</label>
                                <input type="text" name="name" id="inqName" class="form-control" required placeholder="e.g. Gilbert Mponda">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-dark small">Phone Number</label>
                                <input type="tel" name="phone" id="inqPhone" class="form-control" required placeholder="e.g. 0999 899 620">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-dark small">Subject / Purpose</label>
                                <select name="subject" id="inqSubject" class="form-select">
                                    <option value="Egg Incubation Booking">Egg Incubation Booking</option>
                                    <option value="Livestock & Poultry Purchase">Livestock & Poultry Purchase</option>
                                    <option value="Veterinary Vaccines & Ear Tags">Veterinary Vaccines & Ear Tags</option>
                                    <option value="Farmer Training & Consultancy">Farmer Training & Consultancy</option>
                                    <option value="General Agribusiness Inquiry">General Agribusiness Inquiry</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-semibold text-dark small">Message</label>
                                <textarea name="message" id="inqMessage" class="form-control" rows="3" required placeholder="Tell us about your flock, egg quantities, or livestock requirements..."></textarea>
                            </div>
                            <button type="submit" id="inqSubmitBtn" class="btn btn-success fw-bold w-100 py-2 shadow-sm">
                                <i class="bi bi-send-fill me-2"></i>Submit Message
                            </button>
                        </form>

                        <div id="inquiryFeedback" class="mt-3" style="display: none;"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Comprehensive Professional Footer -->
    <footer class="text-white pt-5 pb-4" style="background-color: #0b2914;">
        <div class="container">
            <div class="row g-4 mb-4">
                <div class="col-12 col-md-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <img src="<?= asset('assets/img/logo.png') ?>" alt="MIGS Logo" style="height: 44px; width: auto; filter: brightness(1.2);">
                        <div>
                            <h6 class="fw-bold text-white mb-0">Msantha Investments</h6>
                            <small class="text-white-50">And General Suppliers (MIGS)</small>
                        </div>
                    </div>
                    <p class="text-white-50 small mb-3">
                        Established in 2006. Dedicated to quality livestock breeding, commercial poultry egg incubation, veterinary products, animal ear tagging, and farmer training across Malawi.
                    </p>
                    <p class="text-warning small fw-bold mb-0">
                        "WE ARE BEST AT WHAT WE DO, TRY US AND YOU WON'T REGRET"
                    </p>
                </div>
                <div class="col-6 col-md-2">
                    <h6 class="fw-bold text-white mb-3 text-uppercase small">Quick Links</h6>
                    <ul class="list-unstyled d-flex flex-column gap-2 small text-white-50">
                        <li><a href="#about" class="text-white-50 text-decoration-none hover-white">About Us</a></li>
                        <li><a href="#services" class="text-white-50 text-decoration-none hover-white">Our Services</a></li>
                        <li><a href="#incubation" class="text-white-50 text-decoration-none hover-white">Incubation</a></li>
                        <li><a href="#livestock" class="text-white-50 text-decoration-none hover-white">Livestock</a></li>
                        <li><a href="#contact" class="text-white-50 text-decoration-none hover-white">Contact</a></li>
                        <li><a href="<?= url('/health') ?>" target="_blank" class="text-white-50 text-decoration-none hover-white">System Health</a></li>
                    </ul>
                </div>
                <div class="col-6 col-md-3">
                    <h6 class="fw-bold text-white mb-3 text-uppercase small">Digital Portal</h6>
                    <ul class="list-unstyled d-flex flex-column gap-2 small text-white-50">
                        <li><a href="<?= url('/login') ?>" class="text-warning text-decoration-none fw-bold"><i class="bi bi-box-arrow-in-right me-1"></i>Staff & Farm Sign In</a></li>
                        <li><a href="<?= url('/forgot-password') ?>" class="text-white-50 text-decoration-none hover-white">Forgot Password (SMS)</a></li>
                        <li><a href="<?= asset('downloads/msantha-poultry-farm.apk') ?>" class="text-white-50 text-decoration-none hover-white"><i class="bi bi-android2 me-1"></i>Android Mobile App</a></li>
                        <li><span class="text-white-50"><i class="bi bi-geo-alt me-1 text-warning"></i>Station: Liwonde, Machinga</span></li>
                        <li><span class="text-white-50"><i class="bi bi-cash me-1 text-warning"></i>Currency: Malawi Kwacha (MWK)</span></li>
                    </ul>
                </div>
                <div class="col-12 col-md-3">
                    <h6 class="fw-bold text-white mb-3 text-uppercase small">Contact Details</h6>
                    <ul class="list-unstyled d-flex flex-column gap-2 small text-white-50">
                        <li><i class="bi bi-geo-alt-fill text-warning me-2"></i>Mawira Area by Chipamba Road, Liwonde, Machinga</li>
                        <li><i class="bi bi-mailbox2 text-warning me-2"></i>P.O. Box 250, Liwonde, Machinga</li>
                        <li><i class="bi bi-telephone-fill text-warning me-2"></i>+265 8888 996 20</li>
                        <li><i class="bi bi-telephone-fill text-warning me-2"></i>+265 9998 996 20</li>
                        <li><i class="bi bi-envelope-fill text-warning me-2"></i>icchipeta@gmail.com</li>
                        <li><i class="bi bi-envelope-fill text-warning me-2"></i>icchipeta@yahoo.co.uk</li>
                    </ul>
                </div>
            </div>
            <hr class="border-secondary my-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 small text-white-50">
                <div>
                    &copy; <?= date('Y') ?> <strong>Msantha Investments and General Suppliers (MIGS)</strong>. All rights reserved.
                </div>
                <div>
                    Msantha Poultry Management System &bull; Production v2.0 &bull; Malawi
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5.3 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // 1. Live Incubation Batch Tracker
    const trackForm = document.getElementById('trackIncubationForm');
    const trackBtn = document.getElementById('trackSubmitBtn');
    const trackContainer = document.getElementById('trackResultContainer');

    if (trackForm) {
        trackForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const phone = document.getElementById('trackPhone').value.trim();
            const jobRef = document.getElementById('trackJobRef').value.trim();

            if (!phone && !jobRef) {
                alert('Please enter your mobile phone number.');
                return;
            }

            const origHtml = trackBtn.innerHTML;
            trackBtn.disabled = true;
            trackBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Searching Incubators...';
            trackContainer.style.display = 'block';
            trackContainer.innerHTML = '<div class="text-center py-3 text-muted"><div class="spinner-border text-success" role="status"></div><p class="small mt-2 mb-0">Querying live setter & hatcher database...</p></div>';

            try {
                const res = await fetch('<?= url("/api/public/track-incubation") ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams({ phone: phone, job_ref: jobRef })
                });

                const data = await res.json();
                trackBtn.disabled = false;
                trackBtn.innerHTML = origHtml;

                if (!data.success) {
                    trackContainer.innerHTML = `
                        <div class="alert alert-danger rounded-3 py-2 px-3 small border-0 shadow-sm">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>${data.message || 'Lookup failed.'}
                        </div>`;
                    return;
                }

                if (data.count === 0) {
                    trackContainer.innerHTML = `
                        <div class="alert alert-warning rounded-4 p-3 border-0 shadow-sm text-start">
                            <h6 class="fw-bold text-dark mb-1"><i class="bi bi-info-circle-fill text-warning me-1"></i>No Active Batch Found</h6>
                            <p class="small text-muted mb-2">${data.message}</p>
                            <a href="tel:+265999899620" class="btn btn-outline-dark btn-sm rounded-pill fw-semibold">
                                <i class="bi bi-telephone-fill me-1 text-success"></i>Call +265 9998 996 20
                            </a>
                        </div>`;
                    return;
                }

                let html = `<div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold">${data.count} Batch(es) Found</span>
                    <small class="text-muted font-monospace">Liwonde Hatchery</small>
                </div>`;

                data.jobs.forEach(j => {
                    html += `
                        <div class="card border border-success-subtle shadow-sm rounded-4 mb-3 overflow-hidden text-start">
                            <div class="card-header bg-light border-bottom p-2.5 d-flex justify-content-between align-items-center">
                                <div>
                                    <strong class="text-dark d-block small">${j.customer_name}</strong>
                                    <code class="text-muted" style="font-size: 0.72rem;">${j.batch_code}</code>
                                </div>
                                <span class="badge ${j.badge_class} rounded-pill px-2.5 py-1 fw-bold" style="font-size: 0.75rem;">
                                    ${j.countdown_text}
                                </span>
                            </div>
                            <div class="card-body p-3">
                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <small class="text-muted d-block" style="font-size: 0.7rem;">Breed</small>
                                        <strong class="text-dark small">${j.breed_name}</strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block" style="font-size: 0.7rem;">Eggs Incubated</small>
                                        <strong class="text-success small">${Number(j.eggs_incubated).toLocaleString()} eggs</strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block" style="font-size: 0.7rem;">Day 18 Candling/Transfer</small>
                                        <span class="font-monospace small text-dark">${j.date_transfer}</span>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block" style="font-size: 0.7rem;">Expected Hatch Date</small>
                                        <span class="font-monospace small text-dark fw-bold">${j.expected_hatch_date}</span>
                                    </div>
                                </div>
                                
                                <div class="mt-2 pt-2 border-top">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <small class="text-muted" style="font-size: 0.7rem;">Incubation Stage: <strong>${j.stage_text}</strong></small>
                                        <small class="text-muted font-monospace" style="font-size: 0.7rem;">Day ${j.day_number} of 21</small>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" role="progressbar" style="width: ${j.progress_percent}%;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>`;
                });

                trackContainer.innerHTML = html;
            } catch (err) {
                trackBtn.disabled = false;
                trackBtn.innerHTML = origHtml;
                trackContainer.innerHTML = `<div class="alert alert-danger py-2 px-3 small rounded-3">Connection error. Please try again or call +265 9998 996 20.</div>`;
            }
        });
    }

    // 2. Public Contact Inquiry Form
    const inqForm = document.getElementById('contactInquiryForm');
    const inqBtn = document.getElementById('inqSubmitBtn');
    const inqFeedback = document.getElementById('inquiryFeedback');

    if (inqForm) {
        inqForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const name = document.getElementById('inqName').value.trim();
            const phone = document.getElementById('inqPhone').value.trim();
            const subject = document.getElementById('inqSubject').value.trim();
            const message = document.getElementById('inqMessage').value.trim();

            if (!name || !phone || !message) {
                alert('Please fill out your name, phone number, and message.');
                return;
            }

            const origHtml = inqBtn.innerHTML;
            inqBtn.disabled = true;
            inqBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending to MIGS Management...';
            inqFeedback.style.display = 'none';

            try {
                const res = await fetch('<?= url("/api/public/contact-inquiry") ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams({
                        name: name,
                        phone: phone,
                        subject: subject,
                        message: message
                    })
                });

                const data = await res.json();
                inqBtn.disabled = false;
                inqBtn.innerHTML = origHtml;
                inqFeedback.style.display = 'block';

                if (data.success) {
                    inqForm.reset();
                    inqFeedback.innerHTML = `
                        <div class="alert alert-success border-0 shadow-sm rounded-4 p-3 text-start">
                            <h6 class="fw-bold text-success mb-1 d-flex align-items-center gap-2">
                                <i class="bi bi-check-circle-fill"></i>Inquiry Dispatched Successfully!
                            </h6>
                            <p class="small text-muted mb-0">${data.message}</p>
                        </div>`;
                } else {
                    inqFeedback.innerHTML = `
                        <div class="alert alert-danger border-0 shadow-sm rounded-3 py-2 px-3 small text-start">
                            <i class="bi bi-exclamation-octagon-fill me-1"></i>${data.message || 'Submission failed.'}
                        </div>`;
                }
            } catch (err) {
                inqBtn.disabled = false;
                inqBtn.innerHTML = origHtml;
                inqFeedback.style.display = 'block';
                inqFeedback.innerHTML = `<div class="alert alert-danger py-2 px-3 small rounded-3">Connection error. Please call +265 9998 996 20 directly.</div>`;
            }
        });
    }
    </script>
</body>
</html>

