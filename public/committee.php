<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$db = Database::getInstance()->getConnection();

// ============================================
// FETCH COMMITTEE YEARS
// ============================================
$years = $db->query("
    SELECT * FROM committee_years 
    WHERE status = 1 
    ORDER BY is_current DESC, year_label DESC
")->fetch_all(MYSQLI_ASSOC);

// Selected year
$selectedYearLabel = isset($_GET['year']) ? sanitize($_GET['year']) : '';
$selectedYear = null;

if ($selectedYearLabel) {
    foreach ($years as $y) {
        if ($y['year_label'] === $selectedYearLabel) {
            $selectedYear = $y;
            break;
        }
    }
}

if (!$selectedYear && !empty($years)) {
    foreach ($years as $y) {
        if ($y['is_current']) { $selectedYear = $y; break; }
    }
    if (!$selectedYear) $selectedYear = $years[0];
}

// ============================================
// FETCH MEMBERS GROUPED BY DESIGNATION
// ============================================
$members = [];
$membersByDesignation = [];
$president = null;
$secretary = null;

if ($selectedYear) {
    $stmt = $db->prepare("
        SELECT m.*, d.name as designation_name, d.sort_order as desig_order 
        FROM committee_members m 
        JOIN designations d ON m.designation_id = d.id 
        WHERE m.year_id = ? AND m.status = 1 
        ORDER BY d.sort_order ASC, m.display_order ASC, m.name ASC
    ");
    $stmt->bind_param("i", $selectedYear['id']);
    $stmt->execute();
    $members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($members as $m) {
        $membersByDesignation[$m['designation_name']][] = $m;

        $desigLower = strtolower($m['designation_name']);
        if (!$president && strpos($desigLower, 'president') !== false && strpos($desigLower, 'vice') === false) {
            $president = $m;
        }
        if (!$secretary && (
            strpos($desigLower, 'general secretary') !== false ||
            (strpos($desigLower, 'secretary') !== false && strpos($desigLower, 'joint') === false && strpos($desigLower, 'organizing') === false && strpos($desigLower, 'scientific') === false && strpos($desigLower, 'public') === false)
        )) {
            $secretary = $m;
        }
    }
}

// ============================================
// DUMMY MESSAGES (replace later)
// ============================================
$presidentMessage = "It is a great honor to serve as the President of this esteemed organization. Our committee is dedicated to advancing medical knowledge, fostering collaboration among healthcare professionals, and promoting excellence in patient care. Together, we strive to build a stronger, more connected community of physicians who are committed to continuous learning and professional growth.";
$secretaryMessage = "As the General Secretary, I am committed to ensuring the smooth functioning of our organization and facilitating meaningful engagement among our members. We work tirelessly to organize educational programs, publish valuable resources, and maintain the highest standards of professional integrity. I encourage every member to actively participate in our initiatives and contribute to our shared mission.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Committee - BJDVL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cambria&display=swap" rel="stylesheet">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Cambria', Georgia, serif;
            background: #f5f7fa;
            color: #1a1a2e;
        }

        .main-wrapper { padding: 25px 0 10px; min-height: calc(100vh - 140px); }

        .equal-height-row {
            display: flex;
            align-items: stretch;
        }
        .equal-height-row > [class*="col-"] { display: flex; }
        .equal-height-row > [class*="col-"] > * { width: 100%; }

        /* ============================================
           LEFT SIDEBAR
           ============================================ */
        .members-sidebar {
            background: #ffffff;
            border-radius: 10px;
            border: 1px solid #eef1f5;
            padding: 20px 18px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            display: flex;
            flex-direction: column;
        }

        .bjdvl-logo-block {
            text-align: center;
            padding: 20px 12px;
            background: linear-gradient(180deg, #f8fafc 0%, #eef4fb 100%);
            border-radius: 12px;
            border: 1px solid #e3ebf4;
            margin-bottom: 16px;
        }
        .bjdvl-logo-block .bjdvl-logo-img {
            height: 90px;
            width: auto;
            max-width: 100%;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }

        /* ============================================
           MESSAGE SECTIONS
           ============================================ */
        .message-section { margin-bottom: 12px; }
        .message-section:last-of-type { margin-bottom: 0; }

        .message-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 8px;
            margin-bottom: 12px;
            border-bottom: 1px solid #eef1f5;
        }
        .message-header .role-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: linear-gradient(135deg, #e8f0fe, #d4e2fc);
            color: #0d6efd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            flex-shrink: 0;
        }
        .message-section.secretary .message-header .role-icon {
            background: linear-gradient(135deg, #e8f5e9, #d1fae5);
            color: #198754;
        }
        .message-header .role-title {
            font-size: 0.82rem;
            font-weight: 700;
            color: #1a1a2e;
            line-height: 1.2;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }
        .message-header .role-sub {
            font-size: 0.65rem;
            color: #6c757d;
            font-weight: 500;
            margin-top: 1px;
        }

        .message-author {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: #f8fafc;
            border-radius: 10px;
            border: 1px solid #eef1f5;
            margin-bottom: 12px;
        }
        .message-section.secretary .message-author {
            background: #f0fdf4;
            border-color: #d1fae5;
        }
        .message-author .author-photo {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            overflow: hidden;
            background: linear-gradient(135deg, #e8f0fe, #d4e2fc);
            color: #0d6efd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.15rem;
            flex-shrink: 0;
            border: 2px solid #ffffff;
            box-shadow: 0 2px 8px rgba(13,110,253,0.12);
        }
        .message-section.secretary .message-author .author-photo {
            background: linear-gradient(135deg, #e8f5e9, #d1fae5);
            color: #198754;
            box-shadow: 0 2px 8px rgba(25,135,84,0.12);
        }
        .message-author .author-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .message-author .author-info {
            flex: 1;
            min-width: 0;
        }
        .message-author .author-name {
            font-size: 1.15rem;
            font-weight: 700;
            color: #1a1a2e;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .message-author .author-role {
            font-size: 0.72rem;
            color: #0d6efd;
            font-weight: 700;
            margin-top: 2px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .message-section.secretary .message-author .author-role {
            color: #198754;
        }
        .message-author .author-affil {
            font-size: 0.7rem;
            color: #6c757d;
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .message-text {
            font-size: 0.78rem;
            color: #4a4a5e;
            line-height: 1.55;
            font-style: italic;
            position: relative;
            padding: 0 4px 0 22px;
            display: -webkit-box;
            -webkit-line-clamp: 7;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .message-text::before {
            content: '\201C';
            position: absolute;
            top: -8px;
            left: 0;
            font-size: 2rem;
            color: #0d6efd;
            opacity: 0.25;
            font-family: Georgia, serif;
            line-height: 1;
        }
        .message-section.secretary .message-text::before {
            color: #198754;
        }

        .message-empty {
            text-align: center;
            padding: 14px 10px;
            background: #f8fafc;
            border-radius: 10px;
            border: 1px dashed #e3ebf4;
            color: #6c757d;
            font-size: 0.75rem;
            font-style: italic;
            margin-top: 8px;
        }

        .sidebar-divider {
            height: 1px;
            background: #eef1f5;
            margin: 16px 0;
        }

        /* ============================================
           RIGHT PANEL — Committee Box (GREEN HEADER)
           ============================================ */
        .committee-box {
            background: #ffffff;
            border-radius: 10px;
            border: 1px solid #eef1f5;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            display: flex;
            flex-direction: column;
        }

        .committee-box .box-header {
            background: linear-gradient(135deg, #198754, #146c43);
            padding: 14px 20px;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }
        .committee-box .box-header h5 {
            font-weight: 700;
            font-size: 1.05rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .committee-box .box-header .badge-info {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.68rem;
            background: rgba(255,255,255,0.2);
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: 500;
        }
        .committee-box .box-header .badge-info::before {
            content: '';
            width: 7px;
            height: 7px;
            background: #4ade80;
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: 0.4; } }

        /* Year tabs */
        .year-tabs-inline {
            background: #f8fafb;
            padding: 12px 20px;
            border-bottom: 1px solid #eef1f5;
            display: flex;
            gap: 8px;
            overflow-x: auto;
            scrollbar-width: thin;
            flex-shrink: 0;
        }
        .year-tabs-inline::-webkit-scrollbar { height: 4px; }
        .year-tabs-inline::-webkit-scrollbar-thumb { background: #dee2e6; border-radius: 2px; }

        .year-tab {
            flex-shrink: 0;
            padding: 8px 18px;
            border-radius: 20px;
            border: 1.5px solid #eef1f5;
            background: #ffffff;
            color: #4a4a5e;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }
        .year-tab:hover {
            border-color: #198754;
            color: #198754;
            text-decoration: none;
            transform: translateY(-1px);
        }
        .year-tab.active {
            background: linear-gradient(135deg, #198754, #146c43);
            border-color: #198754;
            color: #fff;
            box-shadow: 0 4px 12px rgba(25,135,84,0.25);
        }
        .year-tab .current-dot {
            width: 7px;
            height: 7px;
            background: #4ade80;
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }

        /* Committee Body */
        .committee-body { padding: 20px; }

        /* ============================================
           DESIGNATION GROUP
           ============================================ */
        .designation-group { margin-bottom: 22px; }
        .designation-group:last-child { margin-bottom: 0; }

        .designation-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eef1f5;
        }
        .designation-header .icon-badge {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: linear-gradient(135deg, #e8f5e9, #d1fae5);
            color: #198754;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            flex-shrink: 0;
        }
        .designation-header h4 {
            font-size: 0.98rem;
            font-weight: 700;
            color: #1a1a2e;
            margin: 0;
            flex: 1;
        }
        .designation-header .count-badge {
            font-size: 0.68rem;
            background: #e8f5e9;
            color: #198754;
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: 700;
        }

        /* ============================================
           ✅ LIST VIEW (Table-style rows)
           ============================================ */
        .member-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .member-row {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 10px 12px;
            background: #ffffff;
            border: 1px solid #eef1f5;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .member-row:hover {
            background: #f8fdfa;
            border-color: #198754;
            box-shadow: 0 2px 10px rgba(25,135,84,0.08);
            transform: translateX(2px);
        }

        /* ✅ Big square photo */
        .member-photo-sq {
            width: 90px;
            height: 90px;
            border-radius: 8px;
            overflow: hidden;
            background: linear-gradient(135deg, #e8f5e9, #d1fae5);
            color: #198754;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 2rem;
            flex-shrink: 0;
            border: 2px solid #ffffff;
            box-shadow: 0 3px 10px rgba(25,135,84,0.12);
        }
        .member-photo-sq img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        /* Member info block */
        .member-info {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .member-info .member-name {
            font-size: 1rem;
            font-weight: 700;
            color: #1a1a2e;
            line-height: 1.25;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .member-info .member-affil {
            font-size: 0.8rem;
            color: #4a4a5e;
            line-height: 1.3;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .member-info .member-affil i {
            color: #198754;
            margin-right: 5px;
            width: 14px;
            font-size: 0.75rem;
        }
        .member-info .member-bio {
            font-size: 0.74rem;
            color: #6c757d;
            line-height: 1.4;
            margin-top: 2px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Contact icons on the right */
        .member-actions {
            display: flex;
            gap: 6px;
            align-items: center;
            flex-shrink: 0;
        }
        .member-actions a {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #e8f5e9;
            color: #198754;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .member-actions a:hover {
            background: #198754;
            color: #fff;
            transform: translateY(-2px);
        }

        /* Empty state */
        .committee-empty {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        .committee-empty i {
            font-size: 3rem;
            color: #dee2e6;
            margin-bottom: 12px;
            display: block;
        }
        .committee-empty h5 {
            font-weight: 700;
            color: #1a1a2e;
            font-size: 1.05rem;
            margin-bottom: 6px;
        }
        .committee-empty p { font-size: 0.88rem; margin: 0; }

        /* ============================================
           RESPONSIVE
           ============================================ */
        @media (max-width: 992px) {
            .member-photo-sq { width: 80px; height: 80px; font-size: 1.7rem; }
        }

        @media (max-width: 768px) {
            .committee-body { padding: 16px; }
            .member-photo-sq { width: 72px; height: 72px; font-size: 1.5rem; }
            .member-row { gap: 12px; padding: 9px 10px; }
            .member-info .member-name { font-size: 0.92rem; }
            .member-info .member-affil { font-size: 0.75rem; }
            .member-actions a { width: 30px; height: 30px; font-size: 0.72rem; }
            .message-text { -webkit-line-clamp: 5; }
            .message-author .author-name { font-size: 1rem; }
            .designation-header h4 { font-size: 0.92rem; }
        }

        @media (max-width: 576px) {
            .bjdvl-logo-block .bjdvl-logo-img { height: 80px; }
            .main-wrapper { padding: 15px 0 10px; }
            .container { padding-left: 10px; padding-right: 10px; }
            .year-tabs-inline { padding: 10px 14px; }
            .year-tab { padding: 6px 14px; font-size: 0.78rem; }
            .committee-body { padding: 14px; }
            .member-photo-sq { width: 64px; height: 64px; font-size: 1.35rem; }
            .member-row { gap: 10px; padding: 8px 10px; }
            .member-info .member-name { font-size: 0.88rem; }
            .member-info .member-affil { font-size: 0.7rem; }
            .member-info .member-bio { -webkit-line-clamp: 1; font-size: 0.68rem; }
            .member-actions a { width: 28px; height: 28px; font-size: 0.68rem; }
            .message-text { font-size: 0.75rem; -webkit-line-clamp: 4; }
            .message-author .author-name { font-size: 0.95rem; }
        }

        @media (max-width: 400px) {
            .member-photo-sq { width: 56px; height: 56px; font-size: 1.2rem; }
            .member-row { gap: 8px; padding: 7px 8px; }
            .member-info .member-name { font-size: 0.82rem; }
            .member-actions { gap: 4px; }
            .member-actions a { width: 24px; height: 24px; font-size: 0.6rem; }
        }

        /* Footer */
        .footer-main {
            background: #ffffff;
            padding: 16px 0;
            border-top: 1px solid #eef1f5;
            margin-top: 12px;
        }
        .footer-main p {
            margin: 0;
            color: #1a1a2e;
            font-size: 0.9rem;
            text-align: center;
        }
        .footer-main p strong { color: #0d6efd; }
        .footer-main p .version { color: #6c757d; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/public_nav.php'; ?>

    <div class="main-wrapper">
        <div class="container">
            <div class="row g-3 equal-height-row">

                <!-- ============================================
                     LEFT SIDEBAR — Logo + Messages
                     ============================================ -->
                <div class="col-lg-4">
                    <div class="members-sidebar">

                        <div class="bjdvl-logo-block">
                            <img src="/assets/images/logo.png"
                                 alt="Logo"
                                 class="bjdvl-logo-img"
                                 onerror="this.style.display='none'">
                        </div>

                        <!-- MESSAGE FROM PRESIDENT -->
                        <div class="message-section president">
                            <div class="message-header">
                                <div class="role-icon"><i class="fas fa-crown"></i></div>
                                <div>
                                    <div class="role-title">Message from</div>
                                    <div class="role-sub">The President</div>
                                </div>
                            </div>

                            <?php if ($president): ?>
                                <div class="message-author">
                                    <div class="author-photo">
                                        <?php if (!empty($president['photo']) && file_exists(__DIR__ . '/uploads/committee/' . $president['photo'])): ?>
                                            <img src="/uploads/committee/<?php echo htmlspecialchars($president['photo']); ?>"
                                                 alt="<?php echo htmlspecialchars($president['name']); ?>">
                                        <?php else: ?>
                                            <?php echo strtoupper(substr($president['name'], 0, 1)); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="author-info">
                                        <div class="author-name"><?php echo htmlspecialchars($president['name']); ?></div>
                                        <div class="author-role"><?php echo htmlspecialchars($president['designation_name']); ?></div>
                                        <?php if (!empty($president['affiliation'])): ?>
                                            <div class="author-affil"><?php echo htmlspecialchars($president['affiliation']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="message-text"><?php echo htmlspecialchars($presidentMessage); ?></div>
                            <?php else: ?>
                                <div class="message-empty">
                                    <i class="fas fa-info-circle"></i> President not assigned for <?php echo $selectedYear ? htmlspecialchars($selectedYear['year_label']) : 'this term'; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="sidebar-divider"></div>

                        <!-- MESSAGE FROM SECRETARY -->
                        <div class="message-section secretary">
                            <div class="message-header">
                                <div class="role-icon"><i class="fas fa-pen-fancy"></i></div>
                                <div>
                                    <div class="role-title">Message from</div>
                                    <div class="role-sub">The General Secretary</div>
                                </div>
                            </div>

                            <?php if ($secretary): ?>
                                <div class="message-author">
                                    <div class="author-photo">
                                        <?php if (!empty($secretary['photo']) && file_exists(__DIR__ . '/uploads/committee/' . $secretary['photo'])): ?>
                                            <img src="/uploads/committee/<?php echo htmlspecialchars($secretary['photo']); ?>"
                                                 alt="<?php echo htmlspecialchars($secretary['name']); ?>">
                                        <?php else: ?>
                                            <?php echo strtoupper(substr($secretary['name'], 0, 1)); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="author-info">
                                        <div class="author-name"><?php echo htmlspecialchars($secretary['name']); ?></div>
                                        <div class="author-role"><?php echo htmlspecialchars($secretary['designation_name']); ?></div>
                                        <?php if (!empty($secretary['affiliation'])): ?>
                                            <div class="author-affil"><?php echo htmlspecialchars($secretary['affiliation']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="message-text"><?php echo htmlspecialchars($secretaryMessage); ?></div>
                            <?php else: ?>
                                <div class="message-empty">
                                    <i class="fas fa-info-circle"></i> Secretary not assigned for <?php echo $selectedYear ? htmlspecialchars($selectedYear['year_label']) : 'this term'; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>

                <!-- ============================================
                     RIGHT PANEL — Committee Box (GREEN HEADER)
                     ============================================ -->
                <div class="col-lg-8">
                    <div class="committee-box">
                        <div class="box-header">
                            <h5><i class="fas fa-users"></i> Our Committee</h5>
                            <span class="badge-info">Live Members</span>
                        </div>

                        <!-- YEAR TABS -->
                        <?php if (!empty($years)): ?>
                        <div class="year-tabs-inline">
                            <?php foreach ($years as $y): 
                                $isActive = $selectedYear && $selectedYear['id'] == $y['id'];
                            ?>
                                <a href="?year=<?php echo urlencode($y['year_label']); ?>" 
                                   class="year-tab <?php echo $isActive ? 'active' : ''; ?>">
                                    <?php if ($y['is_current']): ?>
                                        <span class="current-dot"></span>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($y['year_label']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <!-- BODY — List View -->
                        <div class="committee-body">
                            <?php if (!$selectedYear): ?>
                                <div class="committee-empty">
                                    <i class="fas fa-inbox"></i>
                                    <h5>No Committee Data Yet</h5>
                                    <p>Committee information will appear here once it has been added.</p>
                                </div>
                            <?php elseif (empty($membersByDesignation)): ?>
                                <div class="committee-empty">
                                    <i class="fas fa-user-slash"></i>
                                    <h5>No Members Yet</h5>
                                    <p>No committee members are listed for <?php echo htmlspecialchars($selectedYear['year_label']); ?>.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($membersByDesignation as $designation => $group): ?>
                                    <div class="designation-group">
                                        <div class="designation-header">
                                            <div class="icon-badge">
                                                <i class="fas fa-user-tie"></i>
                                            </div>
                                            <h4><?php echo htmlspecialchars($designation); ?></h4>
                                            <span class="count-badge">
                                                <?php echo count($group); ?> <?php echo count($group) > 1 ? 'Members' : 'Member'; ?>
                                            </span>
                                        </div>

                                        <!-- ✅ LIST VIEW -->
                                        <div class="member-list">
                                            <?php foreach ($group as $m): ?>
                                                <div class="member-row">
                                                    <div class="member-photo-sq">
                                                        <?php if (!empty($m['photo']) && file_exists(__DIR__ . '/uploads/committee/' . $m['photo'])): ?>
                                                            <img src="/uploads/committee/<?php echo htmlspecialchars($m['photo']); ?>" 
                                                                 alt="<?php echo htmlspecialchars($m['name']); ?>" loading="lazy">
                                                        <?php else: ?>
                                                            <?php echo strtoupper(substr($m['name'], 0, 1)); ?>
                                                        <?php endif; ?>
                                                    </div>

                                                    <div class="member-info">
                                                        <div class="member-name"><?php echo htmlspecialchars($m['name']); ?></div>
                                                        <?php if ($m['affiliation']): ?>
                                                            <div class="member-affil">
                                                                <i class="fas fa-building"></i><?php echo htmlspecialchars($m['affiliation']); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if ($m['bio']): ?>
                                                            <div class="member-bio"><?php echo htmlspecialchars($m['bio']); ?></div>
                                                        <?php endif; ?>
                                                    </div>

                                                    <?php if ($m['email'] || $m['phone']): ?>
                                                        <div class="member-actions">
                                                            <?php if ($m['email']): ?>
                                                                <a href="mailto:<?php echo htmlspecialchars($m['email']); ?>" title="Email">
                                                                    <i class="fas fa-envelope"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                            <?php if ($m['phone']): ?>
                                                                <a href="tel:<?php echo htmlspecialchars($m['phone']); ?>" title="Call">
                                                                    <i class="fas fa-phone"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <footer class="footer-main">
        <div class="container">
            <p>
                <strong>BJDVL</strong> &copy; <?php echo date('Y'); ?>
                <span class="version">v2.0</span> &bull;
                <span class="version">Powered by UniMed UniHealth Group</span>
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>