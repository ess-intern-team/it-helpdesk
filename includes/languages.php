<?php
// includes/languages.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php'; // Ensure config.php establishes $pdo connection

function get_languages()
{
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM languages");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in get_languages: " . $e->getMessage());
        return [];
    }
}

function get_current_language()
{
    if (isset($_SESSION['language'])) {
        return $_SESSION['language'];
    }

    global $pdo;
    if (isset($_SESSION['user_id'])) {
        try {
            $stmt = $pdo->prepare("SELECT language FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user_language = $stmt->fetchColumn();
            $_SESSION['language'] = $user_language ?: 'en';
        } catch (PDOException $e) {
            error_log("Database error fetching user language: " . $e->getMessage());
            $_SESSION['language'] = 'en';
        }
    } else {
        $_SESSION['language'] = 'en'; // Default for guests
    }
    return $_SESSION['language'];
}

function set_language($language_code)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT code FROM languages WHERE code = ?");
        $stmt->execute([$language_code]);
        if (!$stmt->fetch()) {
            error_log("Attempted to set an invalid language: " . $language_code);
            return false;
        }
    } catch (PDOException $e) {
        error_log("Database error verifying language: " . $e->getMessage());
        return false;
    }

    $_SESSION['language'] = $language_code;

    if (isset($_SESSION['user_id'])) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET language = ? WHERE id = ?");
            $stmt->execute([$language_code, $_SESSION['user_id']]);
        } catch (PDOException $e) {
            error_log("Database error updating user language: " . $e->getMessage());
        }
    }

    return true;
}

if (!isset($_SESSION['language'])) {
    get_current_language();
}

$translations = [
    'en' => [
        'profile' => 'Profile',
        'change_password' => 'Change Password',
        'logout' => 'Logout',
        'settings' => 'Settings',
        'language' => 'Language',
        'dark_mode' => 'Dark Mode',
        'light_mode' => 'Light Mode',
        'member_since' => 'Member since',
        'full_name' => 'Full Name',
        'email_address' => 'Email Address',
        'update_profile' => 'Update Profile',
        'current_password' => 'Current Password',
        'new_password' => 'New Password',
        'confirm_password' => 'Confirm Password',
        'password_length_hint' => 'Password must be at least 8 characters long.',
        'save_settings' => 'Save Settings',
        'theme' => 'Theme',
        'profile_updated' => 'Profile updated successfully!',
        'password_changed' => 'Password changed successfully!',
        'settings_updated' => 'Settings updated successfully!',
        'update_failed' => 'Failed to update profile. Please try again.',
        'password_change_failed' => 'Failed to change password. Please try again.',
        'settings_update_failed' => 'Failed to update settings. Please try again.',
        'name_email_required' => 'Name and email are required.',
        'invalid_email_format' => 'Invalid email format.',
        'email_in_use' => 'Email already in use by another account.',
        'current_password_required' => 'Current password is required.',
        'incorrect_password' => 'Current password is incorrect.',
        'new_password_required' => 'New password is required.',
        'password_length_error' => 'Password must be at least 8 characters long.',
        'password_mismatch' => 'New passwords do not match.',
        'login_title' => 'Login',
        'register_title' => 'Register',
        'username' => 'Username',
        'password' => 'Password',
        'remember_me' => 'Remember Me',
        'forgot_password' => 'Forgot Password?',
        'dont_have_account' => "Don't have an account?",
        'already_have_account' => "Already have an account?",
        'register_button' => 'Register',
        'login_button' => 'Login',
        'welcome_to_helpdesk' => 'Welcome to ESSA Helpdesk',
        'statistical_service' => 'Ethiopian Statistical Service',
        'helpdesk_description' => 'Your reliable, fast, and modern IT Help Request Tracking System. Built to simplify your support journey and make your work easier.',
        'get_started' => 'Get Started',
        'access_dashboard' => 'Access your dashboard to manage or track tickets.',
        'go_to_dashboard' => 'Go to Dashboard',
        'login_register_hint' => 'Log in or register to submit and track IT support tickets.',
        'home_page_title' => 'IT Help Desk',
    ],
    'ar' => [
        'profile' => 'الملف الشخصي',
        'change_password' => 'تغيير كلمة المرور',
        'logout' => 'تسجيل الخروج',
        'settings' => 'الإعدادات',
        'language' => 'اللغة',
        'dark_mode' => 'الوضع المظلم',
        'light_mode' => 'الوضع الفاتح',
        'member_since' => 'عضو منذ',
        'full_name' => 'الاسم الكامل',
        'email_address' => 'عنوان البريد الإلكتروني',
        'update_profile' => 'تحديث الملف الشخصي',
        'current_password' => 'كلمة المرور الحالية',
        'new_password' => 'كلمة المرور الجديدة',
        'confirm_password' => 'تأكيد كلمة المرور',
        'password_length_hint' => 'يجب أن تتكون كلمة المرور من 8 أحرف على الأقل.',
        'save_settings' => 'حفظ الإعدادات',
        'theme' => 'السمة',
        'profile_updated' => 'تم تحديث الملف الشخصي بنجاح!',
        'password_changed' => 'تم تغيير كلمة المرور بنجاح!',
        'settings_updated' => 'تم تحديث الإعدادات بنجاح!',
        'update_failed' => 'فشل تحديث الملف الشخصي. يرجى المحاولة مرة أخرى.',
        'password_change_failed' => 'فشل تغيير كلمة المرور. يرجى المحاولة مرة أخرى.',
        'settings_update_failed' => 'فشل تحديث الإعدادات. يرجى المحاولة مرة أخرى.',
        'name_email_required' => 'الاسم والبريد الإلكتروني مطلوبان.',
        'invalid_email_format' => 'صيغة البريد الإلكتروني غير صالحة.',
        'email_in_use' => 'البريد الإلكتروني مستخدم بالفعل من قبل حساب آخر.',
        'current_password_required' => 'كلمة المرور الحالية مطلوبة.',
        'incorrect_password' => 'كلمة المرور الحالية غير صحيحة.',
        'new_password_required' => 'كلمة المرور الجديدة مطلوبة.',
        'password_length_error' => 'يجب أن تتكون كلمة المرور من 8 أحرف على الأقل.',
        'password_mismatch' => 'كلمات المرور الجديدة غير متطابقة.',
        'login_title' => 'تسجيل الدخول',
        'register_title' => 'التسجيل',
        'username' => 'اسم المستخدم',
        'password' => 'كلمة المرور',
        'remember_me' => 'تذكرني',
        'forgot_password' => 'نسيت كلمة المرور؟',
        'dont_have_account' => 'ليس لديك حساب؟',
        'already_have_account' => 'لديك حساب بالفعل؟',
        'register_button' => 'تسجيل',
        'login_button' => 'تسجيل الدخول',
        'welcome_to_helpdesk' => 'أهلاً بك في مكتب مساعدة ESSA',
        'statistical_service' => 'خدمة الإحصاء الإثيوبية',
        'helpdesk_description' => 'نظام تتبع طلبات المساعدة التقنية الموثوق به والسريع والحديث. تم بناؤه لتبسيط رحلة الدعم وجعل عملك أسهل.',
        'get_started' => 'ابدأ',
        'access_dashboard' => 'ادخل إلى لوحة التحكم الخاصة بك لإدارة أو تتبع التذاكر.',
        'go_to_dashboard' => 'اذهب إلى لوحة التحكم',
        'login_register_hint' => 'سجل الدخول أو سجل لتقديم تذاكر الدعم الفني وتتبعها.',
        'home_page_title' => 'مكتب مساعدة تكنولوجيا المعلومات',
    ],
    'am' => [
        'profile' => 'መገለጫ',
        'change_password' => 'የይለፍ ቃል ቀይር',
        'logout' => 'ውጣ',
        'settings' => 'ቅንብሮች',
        'language' => 'ቋንቋ',
        'dark_mode' => 'ጨለማ ሞድ',
        'light_mode' => 'ብርሃን ሞድ',
        'member_since' => 'አባል ከ',
        'full_name' => 'ሙሉ ስም',
        'email_address' => 'የኢሜል አድራሻ',
        'update_profile' => 'መገለጫ አዘምን',
        'current_password' => 'አሁን ያለው የይለፍ ቃል',
        'new_password' => 'አዲስ የይለፍ ቃል',
        'confirm_password' => 'የይለፍ ቃል አረጋግጥ',
        'password_length_hint' => 'የይለፍ ቃል ቢያንስ 8 ቁምፊ ሊኖረው ይገባል።',
        'save_settings' => 'ቅንብሮች አስቀምጥ',
        'theme' => 'ገጽታ',
        'profile_updated' => 'መገለጫው በተሳካ ሁኔታ ተዘምኗል!',
        'password_changed' => 'የይለፍ ቃሉ በተሳካ ሁኔታ ተቀይሯል!',
        'settings_updated' => 'ቅንብሮቹ በተሳካ ሁኔታ ተዘምነዋል!',
        'update_failed' => 'መገለጫውን ማዘመን አልተሳካም። እባክዎ ደግመው ይሞክሩ።',
        'password_change_failed' => 'የይለፍ ቃሉን መቀየር አልተሳካም። እባክዎ ደግመው ይሞክሩ።',
        'settings_update_failed' => 'ቅንብሮቹን ማዘመን አልተሳካም። እባክዎ ደግመው ይሞክሩ።',
        'name_email_required' => 'ስም እና ኢሜል ያስፈልጋል።',
        'invalid_email_format' => 'የኢሜል ቅርጸት የተሳሳተ ነው።',
        'email_in_use' => 'ኢሜል ቀድሞውኑ በሌላ መለያ ጥቅም ላይ ይውላል።',
        'current_password_required' => 'አሁን ያለው የይለፍ ቃል ያስፈልጋል።',
        'incorrect_password' => 'አሁን ያለው የይለፍ ቃል የተሳሳተ ነው።',
        'new_password_required' => 'አዲስ የይለፍ ቃል ያስፈልጋል።',
        'password_length_error' => 'የይለፍ ቃል ቢያንስ 8 ቁምፊ ሊኖረው ይገባል።',
        'password_mismatch' => 'አዲሶቹ የይለፍ ቃላት አይዛመዱም።',
        'login_title' => 'ግባ',
        'register_title' => 'ይመዝገቡ',
        'username' => 'የተጠቃሚ ስም',
        'password' => 'የይለፍ ቃል',
        'remember_me' => 'አስታውሰኝ',
        'forgot_password' => 'የይለፍ ቃልዎን ረሱ?',
        'dont_have_account' => 'መለያ የለዎትም?',
        'already_have_account' => 'መለያ አለዎት?',
        'register_button' => 'ይመዝገቡ',
        'login_button' => 'ግባ',
        'welcome_to_helpdesk' => 'እንኳን ወደ ESSA የእርዳታ ጠረጴዛ በደህና መጡ',
        'statistical_service' => 'የኢትዮጵያ ስታትስቲካዊ አገልግሎት',
        'helpdesk_description' => 'ታማኝ፣ ፈጣን እና ዘመናዊ የአይቲ እርዳታ ጥያቄ መከታተያ ስርዓትዎ። የድጋፍ ጉዞዎን ለማቃለል እና ስራዎን ለማቃለል የተገነባ።',
        'get_started' => 'ይጀምሩ',
        'access_dashboard' => 'ትኬቶችን ለማስተዳደር ወይም ለመከታተል የዳሽቦርድዎን ይድረሱ።',
        'go_to_dashboard' => 'ወደ ዳሽቦርድ ይሂዱ',
        'login_register_hint' => 'የአይቲ ድጋፍ ትኬቶችን ለማስገባት እና ለመከታተል ይግቡ ወይም ይመዝገቡ።',
        'home_page_title' => 'የአይቲ እርዳታ ጠረጴዛ',
    ],
    'om' => [
        'profile' => 'Godaansa',
        'change_password' => 'Fuula Labsii jijjiiruu',
        'logout' => 'Baasii',
        'settings' => 'Qindaaʼina',
        'language' => 'Afuura',
        'dark_mode' => 'Dirama dukkanaa',
        'light_mode' => 'Dirama ifaa',
        'member_since' => 'Miseensa erga',
        'full_name' => 'Maqaa guutuu',
        'email_address' => 'Teessoo Imeeli',
        'update_profile' => 'Godaansa haaromsuu',
        'current_password' => 'Labsii amma jiru',
        'new_password' => 'Labsii haaraa',
        'confirm_password' => 'Labsii haaraa mirkaneessuu',
        'password_length_hint' => 'Labsii keessan yoo xiqqaate qajeelchoota 8 qabaachuu qaba.',
        'save_settings' => 'Qindaaʼina qusachuu',
        'theme' => 'Duraa',
        'profile_updated' => 'Godaansi milkaa’inaan haaromfameera!',
        'password_changed' => 'Labsii milkaa’inaan jijjiirameera!',
        'settings_updated' => 'Qindaa’inni milkaa’inaan haaromfameera!',
        'update_failed' => 'Godaansa haaromsuun hin milkoofne. Irra deebi’ii yaali.',
        'password_change_failed' => 'Labsii jijjiiruun hin milkoofne. Irra deebi’ii yaali.',
        'settings_update_failed' => 'Qindaa’ina haaromsuun hin milkoofne. Irra deebi’ii yaali.',
        'name_email_required' => 'Maqaa fi Imeeli barbaachisaadha.',
        'invalid_email_format' => 'Faayila Imeeli sirrii hin taane.',
        'email_in_use' => 'Imeeli duraan akka herrega biraatti fayyadamaa jira.',
        'current_password_required' => 'Labsii amma jiru barbaachisaadha.',
        'incorrect_password' => 'Labsii amma jiru sirrii miti.',
        'new_password_required' => 'Labsii haaraa barbaachisaadha.',
        'password_length_error' => 'Labsii keessan yoo xiqqaate qajeelchoota 8 qabaachuu qaba.',
        'password_mismatch' => 'Labsiiwwan haaraa wal hin fakkaatan.',
        'login_title' => 'Seeni',
        'register_title' => 'Galmaa\'i',
        'username' => 'Maqaa fayyadamaa',
        'password' => 'Labsii',
        'remember_me' => 'Na yaadadhu',
        'forgot_password' => 'Labsii dagatte?',
        'dont_have_account' => 'Herrega hin qabduu?',
        'already_have_account' => 'Herrega qabduu duras?',
        'register_button' => 'Galmaa\'i',
        'login_button' => 'Seeni',
        'welcome_to_helpdesk' => 'Nagaa nagaan gara ESSA Helpdesk dhuftan!',
        'statistical_service' => 'Tajaajila Istaatistikaa Itoophiyaa',
        'helpdesk_description' => 'Sistemii IT Help Request Tracking amanamaa, saffisaa fi ammayyaa. Adeemsa deeggarsaa keessan salphisuuf fi hojii keessan salphisuuf hojjetameera.',
        'get_started' => 'Eegali',
        'access_dashboard' => 'Tikkeettii bulchuuf yookiin hordofuuf daashboordii keessan barbaadi.',
        'go_to_dashboard' => 'Gara daashboordii deemi',
        'login_register_hint' => 'Tikkeettii deeggarsaa IT galchuuf fi hordofuuf seeni yookiin galmaa\'i.',
        'home_page_title' => 'IT Help Desk',
    ],
    'ti' => [
        'profile' => 'መርበብካ',
        'change_password' => 'ቃለ-መሓዛ ለውጢ',
        'logout' => 'ውጻእ',
        'settings' => 'ኣቀማምጣታት',
        'language' => 'ቋንቋ',
        'dark_mode' => 'ጸሊም ሞድ',
        'light_mode' => 'ብሩህ ሞድ',
        'member_since' => 'ኣባል ካብ',
        'full_name' => 'ምሉእ ስም',
        'email_address' => 'ኣድራሻ ኢመይል',
        'update_profile' => 'መርበብካ ኣሕድስ',
        'current_password' => 'ዘሎ ቃለ-መሓዛ',
        'new_password' => 'ሓድሽ ቃለ-መሓዛ',
        'confirm_password' => 'ቃለ-መሓዛ ኣረጋግጽ',
        'password_length_hint' => 'ቃለ-መሓዛ ቢያንስ 8 ሕብረታት ክህልዎ ኣለዎ።',
        'save_settings' => 'ኣቀማምጣታት ኣቀምጥ',
        'theme' => 'ትሕዝቶ',
        'profile_updated' => 'መርበብካ ብዓወት ሓዲሽ ተገይሩ!',
        'password_changed' => 'ቃለ-መሓዛ ብዓወት ተለዊጡ!',
        'settings_updated' => 'ኣቀማምጣታት ብዓወት ሓዲሽ ተገይሩ!',
        'update_failed' => 'መርበብካ ምሕዳስ ኣይከኣለን። እንደገና ፈትን።',
        'password_change_failed' => 'ቃለ-መሓዛ ምልዋጥ ኣይከኣለን። እንደገና ፈትን።',
        'settings_update_failed' => 'ኣቀማምጣታት ምሕዳስ ኣይከኣለን። እንደገና ፈትን።',
        'name_email_required' => 'ስም ከምኡውን ኢመይል ኣድላዪ እዩ።',
        'invalid_email_format' => 'ቅርጺ ኢመይል ዘይግቡእ እዩ።',
        'email_in_use' => 'ኢመይል ኣብ ካልእ ሕሳብ ኣቕድሙ ይጥቀመሉ ኣሎ።',
        'current_password_required' => 'ዘሎ ቃለ-መሓዛ ኣድላዪ እዩ።',
        'incorrect_password' => 'ዘሎ ቃለ-መሓዛ ጌጋ እዩ።',
        'new_password_required' => 'ሓድሽ ቃለ-መሓዛ ኣድላዪ እዩ።',
        'password_length_error' => 'ቃለ-መሓዛ ቢያንስ 8 ሕብረታት ክህልዎ ኣለዎ።',
        'password_mismatch' => 'ሓደስቲ ቃለ-መሓዛታት ኣይሰማምዑን።',
        'login_title' => 'እቶ',
        'register_title' => 'ተመዝገብ',
        'username' => 'ሽም ተጠቃሚ',
        'password' => 'ቃለ-መሓዛ',
        'remember_me' => 'ኣስተውትኒ',
        'forgot_password' => 'ቃለ-መሓዛ ረሲዕካዶ?',
        'dont_have_account' => 'ሕሳብ የብልካንዶ?',
        'already_have_account' => 'ቅድም ሕሳብ ኣለካዶ?',
        'register_button' => 'ተመዝገብ',
        'login_button' => 'እቶ',
        'welcome_to_helpdesk' => 'እንኳዕ ናብ ESSA ህድመተ መርበብ መጸእኩም',
        'statistical_service' => 'ሃገራዊ ኣገልግሎት ስታቲስቲክስ ኢትዮጵያ',
        'helpdesk_description' => 'ዝተኣማመነ፣ ቅልጡፍ፣ ከምኡ’ውን ዘመናዊ ስርዓት ምትሕልላፍ ሓገዝ IT እዩ። ንምርጋግጽን ስራሕካ ንምቅላልን ተሃኒጹ።',
        'get_started' => 'ኣብዛ ጀምር',
        'access_dashboard' => 'ቲኬታት ንምሕደራ ወይ ንምትሕልላፍ ናብ መርበብካ እቶ።',
        'go_to_dashboard' => 'ናብ መርበብካ ሕለፍ',
        'login_register_hint' => 'ቲኬታት ሓገዝ IT ንምልኣኽን ንምትሕልላፍን እቶ ወይ ተመዝገብ።',
        'home_page_title' => 'ህድመተ መርበብ IT',
    ],
    'aa' => [
        'profile' => 'Naagay',
        'change_password' => 'Mabli Gacisa',
        'logout' => 'Adda-elle',
        'settings' => 'Faayida',
        'language' => 'Qasaba',
        'dark_mode' => 'Cakkuub Maala',
        'light_mode' => 'Cakkuub Ifa',
        'member_since' => 'Miseena Abba',
        'full_name' => 'Afaaf Guuti',
        'email_address' => 'Imeeli Teessoo',
        'update_profile' => 'Naagay Digga',
        'current_password' => 'Amma Axxana',
        'new_password' => 'Haada Axxana',
        'confirm_password' => 'Haada Axxana Mirkahiy',
        'password_length_hint' => 'Axxanaada kee yoo xiqqaate 8 haarfii qabaachuu qaba.',
        'save_settings' => 'Faayida Qus',
        'theme' => 'Kaawo',
        'profile_updated' => 'Naagay diggime!',
        'password_changed' => 'Axxana diggime!',
        'settings_updated' => 'Faayida diggime!',
        'update_failed' => 'Naagay diggamuu hin dandeenye. Diggaati yaali.',
        'password_change_failed' => 'Axxana diggamuu hin dandeenye. Diggaati yaali.',
        'settings_update_failed' => 'Faayida diggamuu hin dandeenye. Diggaati yaali.',
        'name_email_required' => 'Afaaf fi Imeeli barbaachisaadha.',
        'invalid_email_format' => 'Imeeli foormaat hin sirriin.',
        'email_in_use' => 'Imeeli duraan akka herrega biraatti fayyadamaa jira.',
        'current_password_required' => 'Amma Axxana barbaachisaadha.',
        'incorrect_password' => 'Amma Axxana hin sirriin.',
        'new_password_required' => 'Haada Axxana barbaachisaadha.',
        'password_length_error' => 'Axxanaada kee yoo xiqqaate 8 haarfii qabaachuu qaba.',
        'password_mismatch' => 'Haada Axxanaadiin wal hin gita.',
        'login_title' => 'Seeni',
        'register_title' => 'Gali',
        'username' => 'Maqaa Fayyadamaa',
        'password' => 'Axxana',
        'remember_me' => 'Na Yaadadhu',
        'forgot_password' => 'Axxana Dagate?',
        'dont_have_account' => 'Herrega hin Qabduu?',
        'already_have_account' => 'Herrega Qabduu Dura?',
        'register_button' => 'Gali',
        'login_button' => 'Seeni',
        'welcome_to_helpdesk' => 'Nagaa nagaan gara ESSA Helpdesk dhuftan!',
        'statistical_service' => 'Tajaajila Istaatistikaa Itoophiyaa',
        'helpdesk_description' => 'Sistemii IT Help Request Tracking amanamaa, saffisaa fi ammayyaa. Adeemsa deeggarsaa keessan salphisuuf fi hojii keessan salphisuuf hojjetameera.',
        'get_started' => 'Eegali',
        'access_dashboard' => 'Tikkeettii bulchuuf yookiin hordofuuf daashboordii keessan barbaadi.',
        'go_to_dashboard' => 'Gara daashboordii deemi',
        'login_register_hint' => 'Tikkeettii deeggarsaa IT galchuuf fi hordofuuf seeni yookiin galmaa\'i.',
        'home_page_title' => 'IT Help Desk',
    ],
    'fr' => [
        'profile' => 'Profil',
        'change_password' => 'Changer le mot de passe',
        'logout' => 'Déconnexion',
        'settings' => 'Paramètres',
        'language' => 'Langue',
        'dark_mode' => 'Mode sombre',
        'light_mode' => 'Mode clair',
        'member_since' => 'Membre depuis',
        'full_name' => 'Nom complet',
        'email_address' => 'Adresse e-mail',
        'update_profile' => 'Mettre à jour le profil',
        'current_password' => 'Mot de passe actuel',
        'new_password' => 'Nouveau mot de passe',
        'confirm_password' => 'Confirmer le mot de passe',
        'password_length_hint' => 'Le mot de passe doit contenir au moins 8 caractères.',
        'save_settings' => 'Enregistrer les paramètres',
        'theme' => 'Thème',
        'profile_updated' => 'Profil mis à jour avec succès !',
        'password_changed' => 'Mot de passe changé avec succès !',
        'settings_updated' => 'Paramètres mis à jour avec succès !',
        'update_failed' => 'Échec de la mise à jour du profil. Veuillez réessayer.',
        'password_change_failed' => 'Échec du changement de mot de passe. Veuillez réessayer.',
        'settings_update_failed' => 'Échec de la mise à jour des paramètres. Veuillez réessayer.',
        'name_email_required' => 'Le nom et l\'e-mail sont requis.',
        'invalid_email_format' => 'Format d\'e-mail invalide.',
        'email_in_use' => 'E-mail déjà utilisé par un autre compte.',
        'current_password_required' => 'Le mot de passe actuel est requis.',
        'incorrect_password' => 'Le mot de passe actuel est incorrect.',
        'new_password_required' => 'Le nouveau mot de passe est requis.',
        'password_length_error' => 'Le mot de passe doit contenir au moins 8 caractères.',
        'password_mismatch' => 'Les nouveaux mots de passe ne correspondent pas.',
        'login_title' => 'Connexion',
        'register_title' => 'Inscription',
        'username' => 'Nom d\'utilisateur',
        'password' => 'Mot de passe',
        'remember_me' => 'Se souvenir de moi',
        'forgot_password' => 'Mot de passe oublié ?',
        'dont_have_account' => "Vous n'avez pas de compte ?",
        'already_have_account' => "Vous avez déjà un compte ?",
        'register_button' => 'S\'inscrire',
        'login_button' => 'Connexion',
        'welcome_to_helpdesk' => 'Bienvenue au service d\'assistance ESSA',
        'statistical_service' => 'Service statistique éthiopien',
        'helpdesk_description' => 'Votre système fiable, rapide et moderne de suivi des demandes d\'aide informatique. Conçu pour simplifier votre parcours d\'assistance et faciliter votre travail.',
        'get_started' => 'Commencer',
        'access_dashboard' => 'Accédez à votre tableau de bord pour gérer ou suivre les tickets.',
        'go_to_dashboard' => 'Aller au tableau de bord',
        'login_register_hint' => 'Connectez-vous ou inscrivez-vous pour soumettre et suivre les tickets d\'assistance informatique.',
        'home_page_title' => 'Service d\'assistance informatique',
    ],
    'zh' => [ // Simplified Chinese (大陆)
        'profile' => '个人资料',
        'change_password' => '更改密码',
        'logout' => '登出',
        'settings' => '设置',
        'language' => '语言',
        'dark_mode' => '深色模式',
        'light_mode' => '浅色模式',
        'member_since' => '注册于',
        'full_name' => '全名',
        'email_address' => '电子邮件地址',
        'update_profile' => '更新个人资料',
        'current_password' => '当前密码',
        'new_password' => '新密码',
        'confirm_password' => '确认密码',
        'password_length_hint' => '密码长度至少为8个字符。',
        'save_settings' => '保存设置',
        'theme' => '主题',
        'profile_updated' => '个人资料更新成功！',
        'password_changed' => '密码更改成功！',
        'settings_updated' => '设置更新成功！',
        'update_failed' => '更新个人资料失败。请重试。',
        'password_change_failed' => '更改密码失败。请重试。',
        'settings_update_failed' => '更新设置失败。请重试。',
        'name_email_required' => '姓名和电子邮件是必填项。',
        'invalid_email_format' => '电子邮件格式无效。',
        'email_in_use' => '电子邮件已被其他账户使用。',
        'current_password_required' => '当前密码是必填项。',
        'incorrect_password' => '当前密码不正确。',
        'new_password_required' => '新密码是必填项。',
        'password_length_error' => '密码长度至少为8个字符。',
        'password_mismatch' => '新密码不匹配。',
        'login_title' => '登录',
        'register_title' => '注册',
        'username' => '用户名',
        'password' => '密码',
        'remember_me' => '记住我',
        'forgot_password' => '忘记密码？',
        'dont_have_account' => "没有账户？",
        'already_have_account' => "已有账户？",
        'register_button' => '注册',
        'login_button' => '登录',
        'welcome_to_helpdesk' => '欢迎来到ESSA帮助台',
        'statistical_service' => '埃塞俄比亚统计服务',
        'helpdesk_description' => '您可靠、快速、现代的IT帮助请求跟踪系统。旨在简化您的支持旅程，让您的工作更轻松。',
        'get_started' => '开始',
        'access_dashboard' => '访问您的仪表板以管理或跟踪工单。',
        'go_to_dashboard' => '前往仪表板',
        'login_register_hint' => '登录或注册以提交和跟踪IT支持工单。',
        'home_page_title' => 'IT帮助台',
    ],
    // Add other languages (om, ti, aa) with similar structure and actual translations.
];

function t($key)
{
    global $translations;
    $lang = get_current_language();
    // Fallback order: current language -> English -> key itself
    return $translations[$lang][$key] ?? $translations['en'][$key] ?? $key;
}

// Handle language change via GET request (e.g., ?lang=ar)
if (isset($_GET['lang'])) {
    $new_lang_code = $_GET['lang'];
    if (set_language($new_lang_code)) {
        // Redirect to remove the ?lang parameter from the URL, preventing issues on refresh
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit();
    } else {
        error_log("Failed to set language from GET parameter: " . $new_lang_code);
    }
}

function getHtmlLangAttribute()
{
    $current_lang = get_current_language();
    $html_lang_map = [
        'en' => 'en',
        'ar' => 'ar',
        'am' => 'am',
        'om' => 'om',
        'ti' => 'ti',
        'aa' => 'aa',
        'fr' => 'fr',
        'zh' => 'zh', // Simplified Chinese
        // Add all your short codes here
    ];
    return $html_lang_map[$current_lang] ?? 'en';
}

function getHtmlDirAttribute()
{
    $current_lang = get_current_language();
    $rtl_languages = ['ar'];
    return in_array($current_lang, $rtl_languages) ? 'rtl' : 'ltr';
}
