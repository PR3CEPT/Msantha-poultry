<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hatch Certificate #<?= $job['id'] ?> — Msantha Poultry</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body { font-family: "Georgia", serif; color: #1e293b; background: #fff; }
        .cert-border {
            max-width: 780px;
            margin: 40px auto;
            padding: 40px;
            border: 8px double #1b6d39;
            background: #ffffff;
            box-shadow: 0 0 20px rgba(0,0,0,0.06);
            text-align: center;
        }
        .cert-title { font-family: "Times New Roman", serif; font-size: 2.2rem; font-weight: bold; color: #1b6d39; letter-spacing: 1px; }
        .cert-subtitle { font-size: 1.1rem; color: #64748b; font-style: italic; }
        .cert-name { font-size: 1.8rem; font-weight: bold; border-bottom: 2px solid #b45309; display: inline-block; padding: 0 30px 5px; margin: 15px 0; color: #0f172a; }
        @media print {
            .no-print { display: none !important; }
            .cert-border { border: 8px double #1b6d39 !important; box-shadow: none; margin: 0 auto; }
        }
    </style>
</head>
<body>
    <div class="cert-border">
        <img src="<?= asset('assets/img/logo.png') ?>" alt="Msantha Poultry Logo" style="width: 75px; height: 75px; object-fit: contain;" class="mb-2">
        <h2 class="cert-title mb-1">MSANTHA POULTRY HATCHERY</h2>
        <div class="cert-subtitle mb-4">Official Certificate of Incubation & Hatching Performance</div>

        <p class="mb-1 text-muted">This is to certify that customer egg batch <strong>#<?= $job['id'] ?></strong> submitted by</p>
        <div class="cert-name"><?= e($job['customer_name']) ?></div>
        <p class="text-muted mb-4">has successfully completed its incubation cycle at our facility.</p>

        <div class="row g-3 justify-content-center my-4 text-center">
            <div class="col-4">
                <div class="p-3 bg-light rounded-3 border">
                    <span class="text-muted small text-uppercase fw-bold d-block">Eggs Set</span>
                    <span class="fs-3 fw-bold text-dark"><?= number_format((int)$job['eggs_incubated']) ?></span>
                </div>
            </div>
            <div class="col-4">
                <div class="p-3 bg-light rounded-3 border">
                    <span class="text-muted small text-uppercase fw-bold d-block">Chicks Hatched</span>
                    <span class="fs-3 fw-bold text-success"><?= number_format((int)$job['hatched']) ?></span>
                </div>
            </div>
            <div class="col-4">
                <div class="p-3 bg-light rounded-3 border">
                    <span class="text-muted small text-uppercase fw-bold d-block">Hatch Rate</span>
                    <span class="fs-3 fw-bold text-primary"><?= !empty($job['hatch_percent']) ? $job['hatch_percent'] . '%' : '—' ?></span>
                </div>
            </div>
        </div>

        <div class="row mt-5 pt-4 text-center small text-muted">
            <div class="col-6">
                <div class="border-top pt-2 mx-4">
                    <strong><?= !empty($job['date_hatched']) ? date('d-M-Y', strtotime($job['date_hatched'])) : date('d-M-Y') ?></strong><br>
                    <span>Date of Hatch & Issue</span>
                </div>
            </div>
            <div class="col-6">
                <div class="border-top pt-2 mx-4">
                    <strong>Hatchery Manager / Quality Overseer</strong><br>
                    <span>Msantha Poultry Operations</span>
                </div>
            </div>
        </div>

        <div class="mt-4 pt-3 no-print">
            <button onclick="window.print()" class="btn btn-success fw-bold px-4 me-2">
                <i class="bi bi-printer"></i> Print Certificate
            </button>
            <button onclick="window.close()" class="btn btn-outline-secondary px-3">Close</button>
        </div>
    </div>
</body>
</html>

