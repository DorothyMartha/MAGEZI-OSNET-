<?php
//session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

function emptyInputsSignup($name, $email, $username, $pwd, $pwdRepeat)
{
    $result = null;
    if (empty($name) || empty($email) || empty($username) || empty($pwd) || empty($pwdRepeat)) {
        $result = true;
    } else {
        $result = false;
    }
    return $result;
}

function invalidUid($username)
{
    $result = null;
    if (preg_match("/^[a-zA-Z0-9]*$/", $username)) {
        $result = true;
    } else {
        $result = false;
    }
    return $result;
}

function invalidEmail($email)
{
    $result = null;
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $result = true;
    } else {
        $result = false;
    }
    return $result;
}

function pwdMatch($pwd, $pwdRepeat)
{
    $result = null;
    if ($pwd !== $pwdRepeat) {
        $result = true;
    } else {
        $result = false;
    }
    return $result;
}

// functions.inc.php - UidExists function
function UidExists($conn, $email, $pwd)
{
    $sql = 'SELECT usersId, usersEmail, usersPwd, usersLevel, usersName FROM users WHERE usersEmail = ?';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $userId, $userEmail, $userPwd, $userLevel, $userName);

    if (mysqli_stmt_fetch($stmt)) {
        if (password_verify($pwd, $userPwd)) {
            return array(
                "usersId" => $userId,
                "usersEmail" => $userEmail,
                "usersPwd" => $userPwd,
                "usersLevel" => $userLevel,
                "usersName" => $userName // Add usersName to the returned array
            );
        }
    }

    return false;

    mysqli_stmt_close($stmt);
}





function  createUser($conn, $name, $email, $level, $pwd)
{
    $sql = 'INSERT INTO users (usersName, usersEmail, usersLevel, usersPwd) VALUES (?,?,?,? );';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo '<script>window.location.href="../signup.php?error=emailtaken";</script>';
        exit();
    }

    $hashedPwd = password_hash($pwd, PASSWORD_DEFAULT);

    mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $level, $hashedPwd);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    echo '<script>window.location.href="../login.php?error=none";</script>';
    exit();
}

// function emptyInputsLogin($name, $pwd) {
//     $result;
//     if (empty($name) || empty($pwd)) {
//         $result = true;
//     } else {
//         $result = false;
//     }
//     return $result;
// }

// login.inc.php
function emptyInputsLogin($email, $pwd)
{
    return empty($email) || empty($pwd);
}


function loginUser($conn, $email, $pwd)
{
    $userData = UidExists($conn, $email, $pwd); // Check if user with email exists and get user data

    if (!$userData) {
        // User with the provided email does not exist or wrong password
        echo '<script>window.location.href="../signup.php?errors=wronglogins";</script>';
        exit();
    }

    // User exists, check if password matches
    $hashedPwd = $userData["usersPwd"];
    if (password_verify($pwd, $hashedPwd)) {
        // Password matches, set session variables and redirect to index.php
        // session_start();
        $_SESSION["userid"] = $userData["usersId"];
        // $_SESSION["useruid"] = $userData["usersUId"];
        $_SESSION["userlevel"] = $userData["usersLevel"];
        $_SESSION["userName"] = $userData["usersName"];
        echo '<script>window.location.href="../index.php";</script>';

        exit();
    } else {
        // Password does not match
        echo '<script>window.location.href="../signup.php?errors=wronglogins";</script>';
        exit();
    }
}

// admins.inc.php
function emptyInputsUser($first_name, $last_name, $contact, $email, $role, $password)
{
    return empty($first_name) || empty($last_name) || empty($email) || empty($contact) || empty($role) || empty($password);
}


function  getAdmins($conn)
{
    $admins = array();

    $sql = 'SELECT id, first_name, last_name, email, contact, role, level, school, student_code, status FROM users';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id, $first_name, $last_name, $email, $contact, $role, $level, $school, $student_code, $status);

    while (mysqli_stmt_fetch($stmt)) {
        $row = array(
            "id" => $id,
            "first_name" => $first_name,
            "last_name" => $last_name,
            "email" => $email,
            "contact" => $contact,
            "role" => $role,
            "level" => $level,
            "school" => $school,
            "status" => $status,
            "student_code" => $student_code,
        );

        array_push($admins, json_encode($row));
    }
    return $admins;

    mysqli_stmt_close($stmt);
}

//get user
function  getUser($conn)
{
    $admins = array();

    $sql = 'SELECT id, first_name, last_name, email, contact, level, school, student_code FROM users WHERE id = ?';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }
    mysqli_stmt_bind_param($stmt, "s", $_SESSION['id']);

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id, $first_name, $last_name, $email, $contact, $level, $school, $student_code);

    while (mysqli_stmt_fetch($stmt)) {
        $row = array(
            "id" => $id,
            "first_name" => $first_name,
            "last_name" => $last_name,
            "email" => $email,
            "contact" => $contact,
            "level" => $level,
            "school" => $school,
            "student_code" => $student_code,
        );

        array_push($admins, json_encode($row));
    }
    return $admins;

    mysqli_stmt_close($stmt);
}

function  getEnrolledTeachers($conn)
{
    $admins = array();

    $sql = 'SELECT id, first_name, last_name, email, contact, role, level, school, status FROM users WHERE role = 3 AND created_by = ?';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_bind_param($stmt, "s", $_SESSION['id']);

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id, $first_name, $last_name, $email, $contact, $role, $level, $school, $status);

    while (mysqli_stmt_fetch($stmt)) {
        $row = array(
            "id" => $id,
            "first_name" => $first_name,
            "last_name" => $last_name,
            "email" => $email,
            "contact" => $contact,
            "role" => $role,
            "level" => $level,
            "school" => $school,
            "status" => $status
        );

        array_push($admins, json_encode($row));
    }
    return $admins;

    mysqli_stmt_close($stmt);
}

//get students created by teacher
function  getStudents($conn)
{
    $admins = array();

    $sql = 'SELECT id, first_name, last_name, email, contact, role, level, school, status FROM users WHERE created_by = ?';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_bind_param($stmt, "s", $_SESSION['id']);

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id, $first_name, $last_name, $email, $contact, $role, $level, $school, $status);

    while (mysqli_stmt_fetch($stmt)) {
        $row = array(
            "id" => $id,
            "first_name" => $first_name,
            "last_name" => $last_name,
            "email" => $email,
            "contact" => $contact,
            "role" => $role,
            "level" => $level,
            "school" => $school,
            "status" => $status
        );

        array_push($admins, json_encode($row));
    }
    return $admins;

    mysqli_stmt_close($stmt);
}

//get student
function  getStudent($conn, $id)
{
    $student = [];

    $sql = 'SELECT id, first_name, last_name FROM users WHERE id = ?';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_bind_param($stmt, "s", $id);

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id, $first_name, $last_name);

    while (mysqli_stmt_fetch($stmt)) {
        $student[0] = $first_name . " " . $last_name;
    }
    return $student;

    mysqli_stmt_close($stmt);
}


//get all revision material
function  getMaterial($conn)
{
    $material = array();

    $sql = 'SELECT id, url, level, subject, topic, subtopic, tag FROM material';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id, $url, $level, $subject, $topic, $subtopic, $tag);

    while (mysqli_stmt_fetch($stmt)) {
        $row = array(
            "id" => $id,
            "url" => $url,
            "level" => $level,
            "subject" => $subject,
            "topic" => $topic,
            "subtopic" => $subtopic,
            "tag" => $tag
        );

        array_push($material, json_encode($row));
    }
    return $material;

    mysqli_stmt_close($stmt);
}

//get all revision student material
function  getStudentMaterial($conn)
{
    $material = array();

    $sql = 'SELECT id, url, level, subject, topic, subtopic FROM material WHERE level = ? GROUP BY subject';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_bind_param($stmt, "s", $_SESSION['level']);

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id, $url, $level, $subject, $topic, $subtopic);

    while (mysqli_stmt_fetch($stmt)) {
        $row = array(
            "id" => $id,
            "url" => $url,
            "level" => $level,
            "subject" => $subject,
            "topic" => $topic,
            "subtopic" => $subtopic
        );

        array_push($material, json_encode($row));
    }
    return $material;

    mysqli_stmt_close($stmt);
}

//get all revision student material
function  getStudentVideos($conn)
{
    $videos = array();

    $sql = 'SELECT id, url, subject, topic, subtopic FROM videos WHERE level = ? GROUP BY subject';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_bind_param($stmt, "s", $_SESSION['level']);

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id, $url, $subject, $topic, $subtopic);

    while (mysqli_stmt_fetch($stmt)) {
        $row = array(
            "id" => $id,
            "url" => $url,
            "subject" => $subject,
            "topic" => $topic,
            "subtopic" => $subtopic
        );

        array_push($videos, json_encode($row));
    }
    return $videos;

    mysqli_stmt_close($stmt);
}

//get all video Content
function  getVideos($conn)
{
    $videos = array();

    $sql = 'SELECT id, url, level, subject, topic, subtopic, tag FROM videos';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id, $url, $level, $subject, $topic, $subtopic, $tag);

    while (mysqli_stmt_fetch($stmt)) {
        $row = array(
            "id" => $id,
            "url" => $url,
            "level" => $level,
            "subject" => $subject,
            "topic" => $topic,
            "subtopic" => $subtopic,
            "tag" => $tag
        );

        array_push($videos, json_encode($row));
    }
    return $videos;

    mysqli_stmt_close($stmt);
}


//get all user roles
function  getRoles($conn)
{
    $roles = array();

    $sql = 'SELECT id, name FROM roles';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id, $name);

    while (mysqli_stmt_fetch($stmt)) {
        $row = array(
            "id" => $id,
            "name" => $name,
        );

        array_push($roles, json_encode($row));
    }
    return $roles;

    mysqli_stmt_close($stmt);
}

//get all schools
function  getSchools($conn)
{
    $schools = array();

    $sql = 'SELECT id, name, levels, center_number FROM schools';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id, $name, $levels, $center_number);

    while (mysqli_stmt_fetch($stmt)) {
        $row = array(
            "id" => $id,
            "name" => $name,
            "center_number" => $center_number,
            "levels" => $levels,
        );

        array_push($schools, json_encode($row));
    }
    return $schools;

    mysqli_stmt_close($stmt);
}

//get school
function  getSchool($conn, $id)
{
    $school = [];

    $sql = 'SELECT name FROM schools WHERE id = ?';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_bind_param($stmt, "s", $id);

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $name);

    while (mysqli_stmt_fetch($stmt)) {
        $school[0] = $name;
    }
    return $school;

    mysqli_stmt_close($stmt);
}

//get all classes
function  getClasses($conn)
{
    $classes = array();

    $sql = 'SELECT id, name, level FROM classes';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id, $name, $level);

    while (mysqli_stmt_fetch($stmt)) {
        $row = array(
            "id" => $id,
            "name" => $name,
            "level" => $level,
        );

        array_push($classes, json_encode($row));
    }
    return $classes;

    mysqli_stmt_close($stmt);
}


//get class
function  getClass($conn, $id)
{
    $class = [];

    $sql = 'SELECT name FROM classes WHERE id = ?';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_bind_param($stmt, "s", $id);

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $name);

    while (mysqli_stmt_fetch($stmt)) {
        $class[0] = $name;
    }

    return $class;

    mysqli_stmt_close($stmt);
}


//get all subjects
function  getSubjects($conn)
{
    $subjects = array();

    $sql = 'SELECT id, name, class FROM subjects';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id, $name, $class);

    while (mysqli_stmt_fetch($stmt)) {
        $row = array(
            "id" => $id,
            "name" => $name,
            "class" => $class,
        );

        array_push($subjects, json_encode($row));
    }
    return $subjects;

    mysqli_stmt_close($stmt);
}

//get all codes
function  getCodes($conn)
{
    $codes = array();

    $sql = 'SELECT id, code FROM codes';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id, $code);

    while (mysqli_stmt_fetch($stmt)) {
        $row = array(
            "id" => $id,
            "code" => $code,
        );

        array_push($codes, json_encode($row));
    }
    return $codes;

    mysqli_stmt_close($stmt);
}


//get all gateways
function  getGateways($conn)
{
    $gateways = array();

    $sql = 'SELECT id, name, status FROM gateways';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id, $name, $status);

    while (mysqli_stmt_fetch($stmt)) {
        $row = array(
            "id" => $id,
            "name" => $name,
            "status" => $status,
        );

        array_push($gateways, json_encode($row));
    }
    return $gateways;

    mysqli_stmt_close($stmt);
}

//get gateway
function  getGateway($conn)
{
    $gateway = [];

    $sql = 'SELECT id, name FROM gateways WHERE status = "on"';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id, $name);

    while (mysqli_stmt_fetch($stmt)) {
        $gateway[0] = $name;
    }
    return $gateway;

    mysqli_stmt_close($stmt);
}


//get subject
function  getSubject($conn, $id)
{
    $subject = [];

    $sql = 'SELECT name FROM subjects WHERE id = ?';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_bind_param($stmt, "s", $id);

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $name);

    while (mysqli_stmt_fetch($stmt)) {
        $subject[0] = $name;
    }

    return $subject;

    mysqli_stmt_close($stmt);
}


//get all topics
function  getTopics($conn)
{
    $topics = array();

    $sql = 'SELECT id, name, subject FROM topics';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id, $name, $subject);

    while (mysqli_stmt_fetch($stmt)) {
        $row = array(
            "id" => $id,
            "name" => $name,
            "subject" => $subject,
        );

        array_push($topics, json_encode($row));
    }
    return $topics;

    mysqli_stmt_close($stmt);
}


//get all topics
function  getStudentTopics($conn, $subject)
{
    $topics = array();

    $sql = 'SELECT id, topic, subtopic FROM material WHERE subject = ? AND level = ? GROUP BY topic';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_bind_param($stmt, "ss", $subject, $_SESSION["level"]);

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id, $topic, $subtopic);

    while (mysqli_stmt_fetch($stmt)) {
        $row = array(
            "id" => $id,
            "topic" => $topic,
            "subtopic" => $subtopic,
        );

        array_push($topics, json_encode($row));
    }
    return $topics;

    mysqli_stmt_close($stmt);
}

//get all topics
function  getStudentVideoTopics($conn, $subject)
{
    $topics = array();

    $sql = 'SELECT id, topic, subtopic FROM videos WHERE subject = ? AND level = ? GROUP BY topic';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_bind_param($stmt, "ss", $subject, $_SESSION["level"]);

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id, $topic, $subtopic);

    while (mysqli_stmt_fetch($stmt)) {
        $row = array(
            "id" => $id,
            "topic" => $topic,
            "subtopic" => $subtopic,
        );

        array_push($topics, json_encode($row));
    }
    return $topics;

    mysqli_stmt_close($stmt);
}

//get topic
function  getTopic($conn, $id)
{
    $topic = [];

    $sql = 'SELECT name FROM topics WHERE id = ?';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_bind_param($stmt, "s", $id);

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $name);

    while (mysqli_stmt_fetch($stmt)) {
        $topic[0] = $name;
    }

    return $topic;

    mysqli_stmt_close($stmt);
}

//get all subtopics
function  getSubTopics($conn)
{
    $subtopics = array();

    $sql = 'SELECT id, name, topic FROM sub_topics';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id, $name, $topic);

    while (mysqli_stmt_fetch($stmt)) {
        $row = array(
            "id" => $id,
            "name" => $name,
            "topic" => $topic,
        );

        array_push($subtopics, json_encode($row));
    }
    return $subtopics;

    mysqli_stmt_close($stmt);
}


//get all subtopics
function  getStudentSubTopics($conn, $topic, $subject)
{
    $subtopics = array();
    $sql = 'SELECT id, url, subtopic FROM material WHERE subject = ? AND topic = ? AND level = ? /*GROUP BY subtopic*/';

    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_bind_param($stmt, "sss", $subject, $topic, $_SESSION["level"]);

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id, $url, $subtopic);

    while (mysqli_stmt_fetch($stmt)) {
        $row = array(
            "id" => $id,
            "url" => $url,
            "subtopic" => $subtopic,
        );

        array_push($subtopics, json_encode($row));
    }
    return $subtopics;

    mysqli_stmt_close($stmt);
}

//get all subtopics
function  getStudentVideoSubTopics($conn, $topic, $subject)
{
    $subtopics = array();
    $sql = 'SELECT id, url, subtopic FROM videos WHERE subject = ? AND topic = ? AND level = ? /*GROUP BY subtopic*/';

    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_bind_param($stmt, "sss", $subject, $topic, $_SESSION["level"]);

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id, $url, $subtopic);

    while (mysqli_stmt_fetch($stmt)) {
        $row = array(
            "id" => $id,
            "url" => $url,
            "subtopic" => $subtopic,
        );

        array_push($subtopics, json_encode($row));
    }
    return $subtopics;

    mysqli_stmt_close($stmt);
}

//get topic
function  getSubTopic($conn, $id)
{
    $subtopic = [];

    $sql = 'SELECT name FROM sub_topics WHERE id = ?';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_bind_param($stmt, "s", $id);

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $name);

    while (mysqli_stmt_fetch($stmt)) {
        $subtopic[0] = $name;
    }

    return $subtopic;

    mysqli_stmt_close($stmt);
}


//get all fees
function  getFees($conn)
{
    $fees = array();

    $sql = 'SELECT id, duration, amount FROM subscription_fees';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id, $duration, $amount);

    while (mysqli_stmt_fetch($stmt)) {
        $row = array(
            "id" => $id,
            "duration" => $duration,
            "amount" => $amount,
        );

        array_push($fees, json_encode($row));
    }
    return $fees;

    mysqli_stmt_close($stmt);
}

//get all categories
function  getCategories($conn)
{
    $categories = array();

    $sql = 'SELECT id, name, amount FROM ticket_categories';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id, $name, $amount);

    while (mysqli_stmt_fetch($stmt)) {
        $row = array(
            "id" => $id,
            "name" => $name,
            "amount" => $amount,
        );

        array_push($categories, json_encode($row));
    }
    return $categories;

    mysqli_stmt_close($stmt);
}

//get all tickets
function  getTickets($conn)
{
    $tickets = array();

    $sql = 'SELECT id, name, email, phone_number, ticket FROM ticket_sales';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id, $name, $email, $phone_number, $ticket);

    while (mysqli_stmt_fetch($stmt)) {
        $row = array(
            "id" => $id,
            "name" => $name,
            "email" => $email,
            "phone_number" => $phone_number,
            "ticket" => $ticket,
        );

        array_push($tickets, json_encode($row));
    }
    return $tickets;

    mysqli_stmt_close($stmt);
}

//get category
function  getCategory($conn, $id)
{
    $category = [];

    $sql = 'SELECT name FROM ticket_categories WHERE id = ?';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_bind_param($stmt, "s", $id);

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $name);

    while (mysqli_stmt_fetch($stmt)) {
        $category[0] = $name;
    }
    return $category;

    mysqli_stmt_close($stmt);
}


//get fee
function  getFee($conn, $id)
{
    $fee = [];

    $sql = 'SELECT id, duration FROM subscription_fees WHERE id = ?';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_bind_param($stmt, "s", $id);

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id, $duration);

    while (mysqli_stmt_fetch($stmt)) {
        $fee[0] = $duration;
    }
    return $fee;

    mysqli_stmt_close($stmt);
}


//get all subscriptions
function  getSubscriptions($conn)
{
    $subscription = array();

    $sql = 'SELECT id, student, plan, start_date, end_date, status, tx_ref, transaction_id, teacher_commission, commission_status FROM subscriptions';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id, $student, $plan, $start_date, $end_date, $status, $tx_ref, $transaction_id, $teacher_commission, $commission_status);

    while (mysqli_stmt_fetch($stmt)) {
        $sql_1 = 'SELECT created_by FROM users WHERE id = ?';
        $stmt_1 = mysqli_stmt_init($conn);

        if (!mysqli_stmt_prepare($stmt_1, $sql_1)) {
            echo "SQL error: " . mysqli_error($conn);
        }

        mysqli_stmt_bind_param($stmt_1, "s", $student);

        mysqli_stmt_execute($stmt_1);
        mysqli_stmt_store_result($stmt_1);

        mysqli_stmt_bind_result($stmt_1, $created_by);

        while (mysqli_stmt_fetch($stmt_1)) {
            $sql_2 = 'SELECT first_name, last_name FROM users WHERE id = ?';
            $stmt_2 = mysqli_stmt_init($conn);

            if (!mysqli_stmt_prepare($stmt_2, $sql_2)) {
                echo "SQL error: " . mysqli_error($conn);
            }

            mysqli_stmt_bind_param($stmt_2, "s", $created_by);

            mysqli_stmt_execute($stmt_2);
            mysqli_stmt_store_result($stmt_2);

            mysqli_stmt_bind_result($stmt_2, $first_name, $last_name);

            while (mysqli_stmt_fetch($stmt_2)) {
                $name = $first_name . " " . $last_name;
            }
        }

        $row = array(
            "id" => $id,
            "student" => $student,
            "plan" => $plan,
            "start_date" => $start_date,
            "end_date" => $end_date,
            "status" => $status,
            "tx_ref" => $tx_ref,
            "transaction_id" => $transaction_id,
            "registered_by" => $name,
            "teacher_commission" => $teacher_commission,
            "commission_status" => $commission_status,
        );

        array_push($subscription, json_encode($row));
    }
    return $subscription;

    mysqli_stmt_close($stmt);
}


//get all subscriptions for a teacher
function  getTeacherSubscriptions($conn)
{
    $subscription = array();

    $sql = 'SELECT id, student, plan, start_date, end_date, status, tx_ref, transaction_id, teacher_commission, commission_status FROM subscriptions';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id, $student, $plan, $start_date, $end_date, $status, $tx_ref, $transaction_id, $teacher_commission, $commission_status);

    while (mysqli_stmt_fetch($stmt)) {
        $sql = 'SELECT id FROM users WHERE id = ? AND created_by = ?';
        $stmt_1 = mysqli_stmt_init($conn);

        if (!mysqli_stmt_prepare($stmt_1, $sql)) {
            echo "SQL error: " . mysqli_error($conn);
        }

        mysqli_stmt_bind_param($stmt_1, "ss", $student, $_SESSION['id']);

        mysqli_stmt_execute($stmt_1);
        mysqli_stmt_store_result($stmt_1);

        if (mysqli_stmt_num_rows($stmt_1) > 0) {
            $row = array(
                "id" => $id,
                "student" => $student,
                "plan" => $plan,
                "start_date" => $start_date,
                "end_date" => $end_date,
                "status" => $status,
                "tx_ref" => $tx_ref,
                "transaction_id" => $transaction_id,
                "teacher_commission" => $teacher_commission,
                "commission_status" => $commission_status,
            );

            array_push($subscription, json_encode($row));
        }
    }

    return $subscription;

    mysqli_stmt_close($stmt);
}

//get all level
function  getLevels($conn)
{
    $levels = array();

    $sql = 'SELECT id, name FROM levels';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id, $name);

    while (mysqli_stmt_fetch($stmt)) {
        $row = array(
            "id" => $id,
            "name" => $name,
        );

        array_push($levels, json_encode($row));
    }
    return $levels;

    mysqli_stmt_close($stmt);
}

//get level
function  getLevel($conn, $id)
{
    $level = [];

    $sql = 'SELECT name FROM levels WHERE id = ?';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_bind_param($stmt, "s", $id);

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $name);

    while (mysqli_stmt_fetch($stmt)) {
        $level[0] = $name;
    }
    return $level;

    mysqli_stmt_close($stmt);
}


//get all teachers
function  getTeachers($conn)
{
    $teachers = array();

    $sql = 'SELECT id, first_name, last_name FROM users WHERE role = 3';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id, $first_name, $last_name);

    while (mysqli_stmt_fetch($stmt)) {
        $row = array(
            "id" => $id,
            "first_name" => $first_name,
            "last_name" => $last_name,
        );

        array_push($teachers, json_encode($row));
    }
    return $teachers;

    mysqli_stmt_close($stmt);
}


//get teacher
function  getTeacher($conn, $id)
{
    $teacher = [];
    $sql = 'SELECT first_name, last_name FROM users WHERE id = ?';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_bind_param($stmt, "s", $id);

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $first_name, $last_name);

    while (mysqli_stmt_fetch($stmt)) {
        $teacher[0] = $first_name . " " . $last_name;
    }

    return $teacher;

    mysqli_stmt_close($stmt);
}


//get all live classes
function  getLiveClasses($conn)
{
    $classes = array();

    if ($_SESSION['role'] == 2) {
        $sql = 'SELECT id, level, subject, topic, teacher, date, time, link FROM class_schedules WHERE level = ?';
    } else {
        $sql = 'SELECT id, level, subject, topic, teacher, date, time, link FROM class_schedules';
    }
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    if ($_SESSION['role'] == 2) {
        mysqli_stmt_bind_param($stmt, "s", $_SESSION['level']);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id, $level, $subject, $topic, $teacher, $date, $time, $link);

    while (mysqli_stmt_fetch($stmt)) {
        $row = array(
            "id" => $id,
            "level" => $level,
            "subject" => $subject,
            "topic" => $topic,
            "teacher" => $teacher,
            "date" => $date,
            "time" => $time,
            "link" => $link,
        );

        array_push($classes, json_encode($row));
    }
    return $classes;

    mysqli_stmt_close($stmt);
}


//get all quizzes
function  getQuizzes($conn)
{
    $quizzes = array();

    $sql = 'SELECT id, name, subject, topic, level, num_of_qns, duration FROM quizzes';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id, $name, $subject, $topic, $level, $num_of_qns, $duration);

    while (mysqli_stmt_fetch($stmt)) {
        $row = array(
            "id" => $id,
            "name" => $name,
            "subject" => $subject,
            "topic" => $topic,
            "level" => $level,
            "num_of_qns" => $num_of_qns,
            "duration" => $duration,
        );

        array_push($quizzes, json_encode($row));
    }
    return $quizzes;

    mysqli_stmt_close($stmt);
}

//get all quizzes
function  getStudentQuizzes($conn)
{
    $quizzes = array();

    $sql = 'SELECT id, name, subject, topic, level, num_of_qns, duration FROM quizzes WHERE level = ?';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_bind_param($stmt, "s", $_SESSION['level']);

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id, $name, $subject, $topic, $level, $num_of_qns, $duration);

    while (mysqli_stmt_fetch($stmt)) {
        $row = array(
            "id" => $id,
            "name" => $name,
            "subject" => $subject,
            "topic" => $topic,
            "level" => $level,
            "num_of_qns" => $num_of_qns,
            "duration" => $duration,
        );

        array_push($quizzes, json_encode($row));
    }
    return $quizzes;

    mysqli_stmt_close($stmt);
}

//get quiz
function  getQuiz($conn, $id)
{
    $quiz = [];

    $sql = 'SELECT id, name FROM quizzes WHERE id = ?';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_bind_param($stmt, "s", $id);

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id, $name);

    while (mysqli_stmt_fetch($stmt)) {
        $quiz[0] = $name;
    }
    return $quiz;

    mysqli_stmt_close($stmt);
}


//get all questions
function  getQuizQuestions($conn)
{
    $questions = array();

    $sql = 'SELECT id, question, option_a, option_b, option_c, option_d, answer FROM quiz_questions';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id, $question, $option_a, $option_b, $option_c, $option_d, $answer);

    while (mysqli_stmt_fetch($stmt)) {
        $row = array(
            "id" => $id,
            "question" => $question,
            "option_a" => $option_a,
            "option_b" => $option_b,
            "option_c" => $option_c,
            "option_d" => $option_d,
            "answer" => $answer,
        );

        array_push($questions, json_encode($row));
    }
    return $questions;

    mysqli_stmt_close($stmt);
}

//get random questions
function  getRandomQuestions($conn, $subject, $topic, $number)
{
    $questions = array();

    $sql = 'SELECT id, question, option_a, option_b, option_c, option_d, answer FROM quiz_questions WHERE subject = ? AND topic = ? ORDER BY RAND() LIMIT ?';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_bind_param($stmt, "sss", $subject, $topic, $number);

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id, $question, $option_a, $option_b, $option_c, $option_d, $answer);

    while (mysqli_stmt_fetch($stmt)) {
        $row = array(
            "id" => $id,
            "question" => $question,
            "option_a" => $option_a,
            "option_b" => $option_b,
            "option_c" => $option_c,
            "option_d" => $option_d,
            "answer" => $answer,
        );

        array_push($questions, json_encode($row));
    }
    return $questions;

    mysqli_stmt_close($stmt);
}

//dashboard statistics
function  getDashboardStatistics($conn)
{
    $statistics = [];
    $students = 0;
    $material = 0;
    $videos = 0;
    $quizzes = 0;
    $total_collection = 0;
    $paid_commission = 0;
    $unpaid_commission = 0;
    $monthly = 0;
    $quarterly = 0;
    $six_months = 0;
    $yearly = 0;

    $sql = 'SELECT id, plan, teacher_commission, commission_status FROM subscriptions';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id, $plan, $teacher_commission, $commission_status);

    while (mysqli_stmt_fetch($stmt)) {
        $sql_1 = 'SELECT id, amount FROM subscription_fees WHERE id = ?';
        $stmt_1 = mysqli_stmt_init($conn);

        if (!mysqli_stmt_prepare($stmt_1, $sql_1)) {
            echo "SQL error: " . mysqli_error($conn);
        }

        mysqli_stmt_bind_param($stmt_1, "s", $plan);

        mysqli_stmt_execute($stmt_1);
        mysqli_stmt_store_result($stmt_1);

        mysqli_stmt_bind_result($stmt_1, $id, $amount);

        while (mysqli_stmt_fetch($stmt_1)) {
            if ($commission_status === "paid") {
                $paid_commission = $paid_commission + $teacher_commission;
            } else {
                $unpaid_commission = $unpaid_commission + $teacher_commission;
            }
            $total_collection = $total_collection + $amount;
        }
    }

    $sql = 'SELECT id FROM users WHERE role = 2';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id);

    while (mysqli_stmt_fetch($stmt)) {
        $students++;
    }

    $sql = 'SELECT id FROM material';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id);

    while (mysqli_stmt_fetch($stmt)) {
        $material++;
    }

    $sql = 'SELECT id FROM videos';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id);

    while (mysqli_stmt_fetch($stmt)) {
        $videos++;
    }

    $sql = 'SELECT id FROM quizzes';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id);

    while (mysqli_stmt_fetch($stmt)) {
        $quizzes++;
    }

    $sql = 'SELECT plan FROM subscriptions';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $plan);

    while (mysqli_stmt_fetch($stmt)) {
        $sql_1 = 'SELECT duration FROM subscription_fees WHERE id = ?';
        $stmt_1 = mysqli_stmt_init($conn);
    
        if (!mysqli_stmt_prepare($stmt_1, $sql_1)) {
            echo "SQL error: " . mysqli_error($conn);
        }
    
        mysqli_stmt_bind_param($stmt_1, "s", $plan);

        mysqli_stmt_execute($stmt_1);
        mysqli_stmt_store_result($stmt_1);
    
        mysqli_stmt_bind_result($stmt_1, $period);
    
        while (mysqli_stmt_fetch($stmt_1)) {
            if ($period === "Monthly") {
                $monthly ++;
            } else if ($period === "Quarterly") {
                $quarterly ++;
            } else if ($period === "Six Months") {
                $six_months ++;
            } else if ($period === "Yearly") {
                $yearly ++;
            }
        }    
    }

    $statistics[0] = $students;
    $statistics[1] = $material;
    $statistics[2] = $videos;
    $statistics[3] = $quizzes;
    $statistics[4] = $total_collection;
    $statistics[5] = $paid_commission;
    $statistics[6] = $unpaid_commission;
    $statistics[7] = $monthly;
    $statistics[8] = $quarterly;
    $statistics[9] = $six_months;
    $statistics[10] = $yearly;

    return $statistics;

    mysqli_stmt_close($stmt);
}

//dashboard statistics
function  getTeacherDashboardStatistics($conn)
{
    $statistics = [];
    $students = 0;
    $material = 0;
    $videos = 0;
    $quizzes = 0;
    $paid_commission = 0;
    $unpaid_commission = 0;

    $sql = 'SELECT id, student, plan, teacher_commission, commission_status FROM subscriptions';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id, $student, $plan, $teacher_commission, $commission_status);

    while (mysqli_stmt_fetch($stmt)) {
        $sql_1 = 'SELECT created_by FROM users WHERE id = ?';
        $stmt_1 = mysqli_stmt_init($conn);

        if (!mysqli_stmt_prepare($stmt_1, $sql_1)) {
            echo "SQL error: " . mysqli_error($conn);
        }

        mysqli_stmt_bind_param($stmt_1, "s", $student);

        mysqli_stmt_execute($stmt_1);
        mysqli_stmt_store_result($stmt_1);

        mysqli_stmt_bind_result($stmt_1, $created_by);

        while (mysqli_stmt_fetch($stmt_1)) {
            if (strval($created_by) === strval($_SESSION['id'])) {
                if ($commission_status === "paid") {
                    $paid_commission = $paid_commission + $teacher_commission;
                } else {
                    $unpaid_commission = $unpaid_commission + $teacher_commission;
                }
            }
        }
    }


    $sql = 'SELECT id FROM users WHERE role = 2 AND created_by = ?';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_bind_param($stmt, "s", $_SESSION['id']);

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id);

    while (mysqli_stmt_fetch($stmt)) {
        $students++;
    }

    $sql = 'SELECT id FROM material WHERE level = ?';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_bind_param($stmt, "s", $_SESSION['level']);

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id);

    while (mysqli_stmt_fetch($stmt)) {
        $material++;
    }

    $sql = 'SELECT id FROM videos WHERE level = ?';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_bind_param($stmt, "s", $_SESSION['level']);

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id);

    while (mysqli_stmt_fetch($stmt)) {
        $videos++;
    }

    $sql = 'SELECT id FROM quizzes';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id);

    while (mysqli_stmt_fetch($stmt)) {
        $quizzes++;
    }

    $statistics[0] = $students;
    $statistics[1] = $material;
    $statistics[2] = $videos;
    $statistics[3] = $quizzes;
    $statistics[4] = $paid_commission;
    $statistics[5] = $unpaid_commission;

    return $statistics;

    mysqli_stmt_close($stmt);
}

//dashboard statistics
function  getStudentDashboardStatistics($conn)
{
    $statistics = [];
    $classes = 0;
    $material = 0;
    $videos = 0;
    $quizzes = 0;


    $sql = 'SELECT id FROM class_schedules WHERE level = ? AND date >= date("Y-m-d H:i:s")';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_bind_param($stmt, "s", $_SESSION['level']);

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id);

    while (mysqli_stmt_fetch($stmt)) {
        $classes++;
    }

    $sql = 'SELECT id FROM material WHERE level = ?';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_bind_param($stmt, "s", $_SESSION['level']);

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id);

    while (mysqli_stmt_fetch($stmt)) {
        $material++;
    }

    $sql = 'SELECT id FROM videos WHERE level = ?';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_bind_param($stmt, "s", $_SESSION['level']);

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id);

    while (mysqli_stmt_fetch($stmt)) {
        $videos++;
    }

    $sql = 'SELECT id FROM quizzes';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    // mysqli_stmt_bind_param($stmt, "s", $_SESSION['level']);

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id);

    while (mysqli_stmt_fetch($stmt)) {
        $quizzes++;
    }

    $statistics[0] = $classes;
    $statistics[1] = $material;
    $statistics[2] = $videos;
    $statistics[3] = $quizzes;

    return $statistics;

    mysqli_stmt_close($stmt);
}


//get subscription fees
function  getSubscriptionFees($conn)
{
    $fees = [];

    $sql = 'SELECT id, duration, amount FROM subscription_fees';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    mysqli_stmt_bind_result($stmt, $id, $duration, $amount);

    while (mysqli_stmt_fetch($stmt)) {
        $row = array(
            "id" => $id,
            "duration" => $duration,
            "amount" => $amount,
        );

        array_push($fees, json_encode($row));
    }
    return $fees;

    mysqli_stmt_close($stmt);
}
