<?php
session_start();

if (!isset($_SESSION['id_personnel_tasks'])) {
    header("Location: ../index.php");
    exit();
}

// if ($_SESSION['acces_rh'] != 1) {
//     header('Location: ../acces_refuse.php');
// }

require_once '../model/Personnel.php';
require_once '../model/Helper.php';
require_once '../model/Fonction.php';
require_once '../model/Service.php';

$personnelObj = new Personnel();
$helperObj = new Helper();
$fonctionObj = new Fonction();
$serviceObj = new Service();

if (isset($_SESSION['id_personnel_tasks'])) {
    $employeeId = $_SESSION['id_personnel_tasks'];
    $employeeDetails = $personnelObj->getPersonnelById($employeeId);
    // Supposons que vous récupérez également les données d'assiduité et de rendement ici.
    $attendanceData = $personnelObj->getAttendanceDataById($employeeId);
    $tasksData = $personnelObj->getTasksDataById($employeeDetails['matricule_personnel_tasks']);

    $date_entree = $helperObj->dateEnFrancaisSansHeure($employeeDetails['date_recrutement']);

    $fonction = $fonctionObj->obtenirFonctionParId($employeeDetails['fonction_id']);

    $service = $serviceObj->obtenirServiceParId($employeeDetails['service_id']);

    $allService = $serviceObj->listerServices();
}
