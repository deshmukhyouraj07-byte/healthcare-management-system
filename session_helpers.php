<?php
/**
 * session_helpers.php
 *
 * Shared helper for auto-logging-out staff/doctor sessions after a period
 * of inactivity. Call enforceStaffSessionTimeout() right after session_start()
 * on any page that checks $_SESSION['staff_id'] (portal.php, print_receipt.php,
 * view_payment_proof.php, etc.) — BEFORE reading any other staff session data.
 *
 * How it works: every authenticated request "touches" staff_last_activity.
 * If more than $timeoutSeconds pass between requests (e.g. the person
 * navigates away to another page/site and comes back later), the staff
 * session is cleared and they'll see a fresh login form again — this does
 * NOT log out a patient session, only the staff/doctor one.
 */

function enforceStaffSessionTimeout(int $timeoutSeconds = 120): void
{
    if (!isset($_SESSION['staff_id'])) {
        return;
    }

    $lastActivity = $_SESSION['staff_last_activity'] ?? null;

    if ($lastActivity !== null && (time() - $lastActivity) > $timeoutSeconds) {
        unset(
            $_SESSION['staff_id'],
            $_SESSION['staff_username'],
            $_SESSION['staff_name'],
            $_SESSION['staff_role'],
            $_SESSION['can_provision'],
            $_SESSION['staff_last_activity']
        );
        return;
    }

    $_SESSION['staff_last_activity'] = time();
}
