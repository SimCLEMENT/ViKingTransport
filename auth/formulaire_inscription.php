<?php

session_start();

include_once "../PHP/pdo_agile.php";
include_once "../PHP/param_connexion.php";
echo '<meta charset="utf-8">';

define("MOD_BDD", "ORACLE");

if (MOD_BDD == "MYSQL") {
    $db_username = $db_usernameMySQL;
    $db_password = $db_passwordMySQL;
    $db = $dbMySQL;
} else {
    $db_username = $db_usernameOracle;
    $db_password = $db_passwordOracle;
    $db = $dbOracle;
}

$conn = OuvrirConnexionPDO($db, $db_username, $db_password);

if ($conn) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($_POST['cli_mdp'] !== $_POST['cli_mdp_confirm']) {
            echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px; font-family: sans-serif; border: 1px solid #f5c6cb;'>";
            echo "<strong>Erreur :</strong> Les mots de passe ne correspondent pas.";
            echo "</div>";
        } else {
            $queryEmails = $conn->prepare("SELECT cli_courriel FROM vik_client");
            $queryEmails->execute();

            foreach ($queryEmails->fetchAll(PDO::FETCH_COLUMN) as $email) {
                if ($_POST["cli_courriel"] == $email) {
                    $_SESSION["error"] = "alreadyExists";
                    header("Location: /auth/formulaire_site.php");
                    die();
                }
            }

            insererNouveauClient($conn, $_POST);
        }
    } else {
        echo "<p style='margin: 20px; font-family: sans-serif;'>En attente de la soumission du formulaire... Veuillez passer par la page d'inscription.</p>";
    }
} else {
    echo "<hr/> Connexion impossible à la base de données. Vérifiez vos paramètres dans param_connexion.php. <br/>";
}

function insererNouveauClient($c, $donneesPost)
{
    $sql_max = "SELECT MAX(CLI_NUM) AS MAX_ID FROM VIK_CLIENT";
    $tab_resultat = array();

    LireDonneesPDO3($c, $sql_max, $tab_resultat);

    $max_id = $tab_resultat[0]['MAX_ID'];

    if ($max_id == null) {
        $nouveau_cli_num = 1;
    } else {
        $nouveau_cli_num = $max_id + 1;
    }

    $telephone_propre = str_replace([' ', '.', '-'], '', $donneesPost['cli_telephone']);
    $nom_propre = strtoupper($donneesPost['cli_nom']);

    $sql = "INSERT INTO VIK_CLIENT
    (CLI_NUM,TYP_NUM,DEP_NUM,CLI_NOM,CLI_PRENOM,CLI_VILLE,CLI_TELEPHONE,CLI_COURRIEL,CLI_MDP,CLI_NB_POINTS_EC,CLI_NB_POINTS_TOT,CLI_DATE_CONNEC)
    VALUES
    (:cli_num,1,:dep_num,:nom,:prenom,:ville,:telephone,:courriel,:mdp,0,0,SYSDATE)";

    $cur = preparerRequetePDO($c, $sql);

    $dep_num      = $donneesPost['dep_num'];
    $prenom       = $donneesPost['cli_prenom'];
    $ville        = $donneesPost['cli_ville'];
    $courriel     = $donneesPost['cli_courriel'];
    $mot_de_passe = password_hash($donneesPost['cli_mdp'], PASSWORD_DEFAULT);

    ajouterParamPDO($cur, ':cli_num',   $nouveau_cli_num,  'nombre');
    ajouterParamPDO($cur, ':dep_num',   $dep_num,          'texte');
    ajouterParamPDO($cur, ':nom',       $nom_propre,       'texte');
    ajouterParamPDO($cur, ':prenom',    $prenom,           'texte');
    ajouterParamPDO($cur, ':ville',     $ville,            'texte');
    ajouterParamPDO($cur, ':telephone', $telephone_propre, 'texte');
    ajouterParamPDO($cur, ':courriel',  $courriel,         'texte');
    ajouterParamPDO($cur, ':mdp',       $mot_de_passe,     'texte');

    $res = majDonneesPrepareesPDO($cur);

    if ($res) {
        $_SESSION["error"] = "accCreated";
        header("Location: /auth/connexion.php");
        die();
    } else {
        echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px; font-family: sans-serif; border: 1px solid #f5c6cb;'>";
        echo "<strong>Erreur :</strong> Impossible d'ajouter le client dans la base de données. Vérifiez l'intégrité de vos données.";
        echo "</div>";
    }
}

function afficherObj($obj)
{
    echo "<PRE>";
    print_r($obj);
    echo "</PRE>";
}
?>
