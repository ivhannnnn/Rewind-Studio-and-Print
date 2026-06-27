<?php
session_start();
include 'db.php';
 
// ── Safe current page ─────────────────────────────────────────────────────────
$current = basename($_SERVER['PHP_SELF']);
 
// ── Services list ─────────────────────────────────────────────────────────────
// NOTE: Move this to a `services` DB table and replace with mysqli_query()
//       to let admins manage services without editing code.
$services = [
    [
        'name'  => 'Wedding',
        'desc'  => 'Capture your special day with full cinematic coverage — from ceremony to reception.',
        'price' => 15000,
        'icon'  => '💍',
    ],
    [
        'name'  => 'Debut',
        'desc'  => 'Celebrate your 18th birthday with elegant portraits and a personalised highlight reel.',
        'price' => 8000,
        'icon'  => '🌸',
    ],
    [
        'name'  => 'Graduation',
        'desc'  => 'Professional graduation photoshoot and coverage to mark your milestone.',
        'price' => 3000,
        'icon'  => '🎓',
    ],
    [
        'name'  => 'Pre-Birthday',
        'desc'  => 'Creative themed photoshoots tailored to your personality and style.',
        'price' => 3500,
        'icon'  => '🎂',
    ],
    [
        'name'  => 'Prenup',
        'desc'  => 'Romantic pre-nuptial storytelling shoots that set the mood for your big day.',
        'price' => 10000,
        'icon'  => '💑',
    ],
];
 
// ── Calendar events query ─────────────────────────────────────────────────────
$events = [];
 
$eventsQuery = mysqli_query($conn, "
    SELECT event_date, service_name, status
    FROM bookings
    WHERE status IN ('Approved', 'Confirmed', 'Completed')
");
 
if (!$eventsQuery) {
    error_log("Bookings calendar query failed: " . mysqli_error($conn));
} else {
    $colorMap = [
        'Approved'  => '#4CAF50',
        'Confirmed' => '#e67e22',
        'Completed' => '#3498db',
    ];
 
    while ($row = mysqli_fetch_assoc($eventsQuery)) {
        $color    = $colorMap[$row['status']] ?? '#999';
        $events[] = [
            'title'           => htmlspecialchars($row['service_name'], ENT_QUOTES, 'UTF-8'),
            'start'           => $row['event_date'],
            'backgroundColor' => $color,
            'borderColor'     => $color,
            'textColor'       => '#fff',
            'extendedProps'   => ['status' => $row['status']],
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Services – Rewind Studio and Prints</title>
    <link rel="icon" type="image/jpeg" href="logo.jpg">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="services_v11.css">
 
    <style>
        /* ── Modal overlay ───────────────────────────────────────────────────── */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .55);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            padding: 1rem;
        }
        .modal-overlay.open { display: flex; }
 
        .modal-box {
            background: #fff;
            border-radius: 14px;
            padding: 2rem 2.25rem;
            max-width: 420px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,.3);
            position: relative;
            animation: modalIn .22s ease;
        }
        @keyframes modalIn {
            from { transform: translateY(30px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }
 
        .modal-close {
            position: absolute;
            top: .85rem; right: 1rem;
            background: none;
            border: none;
            font-size: 1.4rem;
            cursor: pointer;
            color: #666;
            line-height: 1;
        }
        .modal-close:hover { color: #222; }
 
        .modal-icon  { font-size: 2.2rem; margin-bottom: .5rem; }
        .modal-title { font-size: 1.35rem; font-weight: 700; margin: 0 0 .4rem; }
        .modal-date  { color: #555; margin-bottom: .75rem; font-size: .95rem; }
        .modal-status-badge {
            display: inline-block;
            padding: .25rem .75rem;
            border-radius: 20px;
            font-size: .8rem;
            font-weight: 600;
            color: #fff;
        }
 
        /* ── Calendar legend ─────────────────────────────────────────────────── */
        .calendar-legend {
            display: flex;
            gap: 1.25rem;
            flex-wrap: wrap;
            justify-content: center;
            margin: 1.25rem 0 0;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: .45rem;
            font-size: .88rem;
            color: #444;
        }
        .legend-dot {
            width: 13px; height: 13px;
            border-radius: 50%;
            flex-shrink: 0;
        }
 
        /* ── Availability toast ──────────────────────────────────────────────── */
        .toast {
            position: fixed;
            bottom: 1.5rem; left: 50%;
            transform: translateX(-50%) translateY(80px);
            background: #222;
            color: #fff;
            padding: .65rem 1.4rem;
            border-radius: 30px;
            font-size: .92rem;
            z-index: 10000;
            transition: transform .3s ease, opacity .3s ease;
            opacity: 0;
            pointer-events: none;
        }
        .toast.show {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }
        .toast.available { background: #2e7d32; }
        .toast.taken     { background: #c0392b; }
    </style>
</head>
<body>
 
<!-- ═══════════════════════════ HEADER ═══════════════════════════════════════ -->
<header>
    <div class="logo">
        <img src="385319258_714193217393851_8500146797645932462_n (1).jpg"
             alt="Rewind Studio and Prints Logo">
        <span>Rewind Studio and Prints</span>
    </div>
 
    <nav class="nav" aria-label="Main navigation">
        <a href="dashboard.php"
           class="<?= $current === 'dashboard.php' ? 'active' : '' ?>"
           aria-current="<?= $current === 'dashboard.php' ? 'page' : 'false' ?>">
            Home
        </a>
        <a href="services.php"
           class="<?= $current === 'services.php' ? 'active' : '' ?>"
           aria-current="<?= $current === 'services.php' ? 'page' : 'false' ?>">
            Services
        </a>
 
        <?php if (isset($_SESSION['user'])): ?>
            <a href="user_messages.php"
               class="<?= $current === 'user_messages.php' ? 'active' : '' ?>"
               aria-current="<?= $current === 'user_messages.php' ? 'page' : 'false' ?>">
                Messages
            </a>
            <a href="booking_status.php"
               class="<?= $current === 'booking_status.php' ? 'active' : '' ?>"
               aria-current="<?= $current === 'booking_status.php' ? 'page' : 'false' ?>">
                Booking Status
            </a>
            <a href="logout.php" class="logout-btn">Logout</a>
        <?php else: ?>
            <a href="login.php"
               class="<?= $current === 'login.php' ? 'active' : '' ?>"
               aria-current="<?= $current === 'login.php' ? 'page' : 'false' ?>">
                Login
            </a>
        <?php endif; ?>
    </nav>
</header>
 
<!-- ═══════════════════════════ SERVICES ════════════════════════════════════ -->
<section class="services">
 
    <?php foreach ($services as $service):
        $downpayment = $service['price'] * 0.30;
    ?>
    <div class="card">
 
        <div class="service-header">
            <div>
                <span class="service-icon" aria-hidden="true"><?= $service['icon'] ?></span>
                <h2><?= htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                <span class="status-badge">Available</span>
            </div>
        </div>
 
        <div class="service-body">
            <p class="description">
                <?= htmlspecialchars($service['desc'], ENT_QUOTES, 'UTF-8') ?>
            </p>
 
            <div class="price-card">
                <small>Starting Price</small>
                <h3>₱<?= number_format($service['price'], 2) ?></h3>
                <div class="down-payment">
                    <span>Required Down Payment (30%)</span>
                    <strong>₱<?= number_format($downpayment, 2) ?></strong>
                </div>
            </div>
 
            <!-- Workflow steps — decorative/informational only, not user-specific -->
            <div class="workflow" aria-label="Booking process">
                <div class="step">
                    <span aria-hidden="true">1.</span> Submit Booking
                </div>
                <div class="step">
                    <span aria-hidden="true">2.</span> Admin Approval
                </div>
                <div class="step">
                    <span aria-hidden="true">3.</span> Pay Down Payment
                </div>
                <div class="step">
                    <span aria-hidden="true">4.</span> Booking Confirmed
                </div>
            </div>
        </div>
 
        <?php if (isset($_SESSION['user'])): ?>
            <a href="book.php?service=<?= urlencode($service['name']) ?>"
               class="btn"
               title="Book <?= htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') ?>">
                Book Appointment
            </a>
        <?php else: ?>
            <a href="login.php" class="btn">Login to Book</a>
        <?php endif; ?>
 
    </div>
    <?php endforeach; ?>
 
</section>
 
<!-- ═══════════════════════════ CALENDAR ════════════════════════════════════ -->
<section class="calendar-section">
    <h2>Booking Calendar</h2>
    <p class="calendar-subtitle">
        Click any date to check availability. Click a booked event for details.
    </p>
 
    <div id="calendar"></div>
 
    <!-- Legend -->
    <div class="calendar-legend" aria-label="Calendar colour legend">
        <div class="legend-item">
            <span class="legend-dot" style="background:#4CAF50"></span>
            Approved
        </div>
        <div class="legend-item">
            <span class="legend-dot" style="background:#e67e22"></span>
            Confirmed
        </div>
        <div class="legend-item">
            <span class="legend-dot" style="background:#3498db"></span>
            Completed
        </div>
    </div>
</section>
 
<!-- ═══════════════════════════ EVENT MODAL ══════════════════════════════════ -->
<div class="modal-overlay" id="eventModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="modal-box">
        <button class="modal-close" id="modalClose" aria-label="Close">&times;</button>
        <div class="modal-icon" id="modalIcon"></div>
        <h3 class="modal-title" id="modalTitle"></h3>
        <p class="modal-date"  id="modalDate"></p>
        <span class="modal-status-badge" id="modalStatus"></span>
    </div>
</div>
 
<!-- ═══════════════════════════ TOAST ════════════════════════════════════════ -->
<div class="toast" id="toast" role="status" aria-live="polite"></div>
 
<!-- ═══════════════════════════ SCRIPTS ══════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
 
    // ── Data from PHP ──────────────────────────────────────────────────────
    const events = <?php echo json_encode($events, JSON_HEX_TAG | JSON_HEX_AMP); ?>;
 
    // ── DOM refs ───────────────────────────────────────────────────────────
    const modal       = document.getElementById('eventModal');
    const modalClose  = document.getElementById('modalClose');
    const modalIcon   = document.getElementById('modalIcon');
    const modalTitle  = document.getElementById('modalTitle');
    const modalDate   = document.getElementById('modalDate');
    const modalStatus = document.getElementById('modalStatus');
    const toast       = document.getElementById('toast');
 
    // ── Status colour map (mirrors PHP) ───────────────────────────────────
    const statusColors = {
        Approved:  '#4CAF50',
        Confirmed: '#e67e22',
        Completed: '#3498db',
    };
 
    // ── Toast helper ───────────────────────────────────────────────────────
    let toastTimer;
    function showToast(message, type = 'available') {
        clearTimeout(toastTimer);
        toast.textContent  = message;
        toast.className    = 'toast show ' + type;
        toastTimer = setTimeout(() => { toast.className = 'toast'; }, 3000);
    }
 
    // ── Modal helpers ──────────────────────────────────────────────────────
    function openModal(title, dateStr, status) {
        const formatted = new Date(dateStr + 'T00:00:00').toLocaleDateString('en-PH', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        });
        const icons = {
            Wedding: '💍', Debut: '🌸', Graduation: '🎓',
            'Pre-Birthday': '🎂', Prenup: '💑',
        };
 
        modalIcon.textContent   = icons[title] ?? '📅';
        modalTitle.textContent  = title;
        modalDate.textContent   = formatted;
        modalStatus.textContent = status;
        modalStatus.style.background = statusColors[status] ?? '#999';
 
        modal.classList.add('open');
        modalClose.focus();
    }
 
    function closeModal() {
        modal.classList.remove('open');
    }
 
    modalClose.addEventListener('click', closeModal);
 
    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });
 
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeModal();
    });
 
    // ── Calendar ───────────────────────────────────────────────────────────
    const calendarEl = document.getElementById('calendar');
 
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView:    'dayGridMonth',
        height:         700,
        fixedWeekCount: false,
        navLinks:       true,
        selectable:     true,
        nowIndicator:   true,
 
        headerToolbar: {
            left:   'prev,next today',
            center: 'title',
            right:  'dayGridMonth,timeGridWeek',
        },
 
        events: events,
        eventDisplay: 'block',
 
        // Click on a booked event → open styled modal
        eventClick: function (info) {
            const status = info.event.extendedProps.status ?? 'Booked';
            openModal(info.event.title, info.event.startStr, status);
        },
 
        // Click on a date → show toast (available / taken)
        dateClick: function (info) {
            const taken = calendar.getEvents().some(
                ev => ev.startStr === info.dateStr
            );
            if (taken) {
                showToast('❌ This date is already reserved.', 'taken');
            } else {
                showToast('✅ This date is available.', 'available');
            }
        },
    });
 
    calendar.render();
});
</script>
 
</body>
</html>