	<?php
	// E.Porcq : Saison 5 , Épisode 3 
	// préparation SAE 2.456 : Traitement d'un formualaire
	// traitement_form.php 29/05/2021

	function afficherObj($obj)
	{
		echo "<PRE>";
		print_r($obj);
		echo "</PRE>";
	}

	include_once "pdo_agile.php";
	include_once "param_connexion.php";
	echo '<meta charset="utf-8"> ';
	//define ("MOD_BDD","MYSQL");
	define ("MOD_BDD","ORACLE");

	if (MOD_BDD == "MYSQL")
	{
		$db_username = $db_usernameMySQL;		
		$db_password = $db_passwordMySQL;
		$db = $dbMySQL;
	}
	else
	{
		$db_username = $db_usernameOracle;		
		$db_password = $db_passwordOracle;	
		$db = $dbOracle;
	}

	$conn = OuvrirConnexionPDO($db,$db_username,$db_password);

	// affichage brut des éléments du formulaire
	afficherObj($_POST);

	$erreur=false; // true => formulaire défaut
	
	// il faut vérifier que ces données ont été saisies
	if (!empty($_POST["nom"]) )
		$nom = $_POST["nom"];
	else
		$erreur= true;

	if (!empty($_POST["prenom"]) )
		$prenom = $_POST["prenom"];
	else
		$erreur= true;

	if (!empty($_POST["code"]) )
		$code = $_POST["code"];
	else
		$erreur= true;

	if (isset($_POST["genre"]) )
		$genre = $_POST["genre"];
	else
		$erreur= true;		

	if (isset($_POST["pays"]) )
		$pays = $_POST["pays"];
	else
		$erreur= true;	

	if (isset($_POST["preference"]) )
		$preference = $_POST["preference"];
	else
		$erreur= true;	

	if (!empty($_POST["gouts"]) )
		$gouts = $_POST["gouts"];
	else
		$erreur= true;	
	
	if (	$erreur == false )
	{	
		$sql = "INSERT INTO personne (per_nom, per_prenom, per_mdp, per_genre, per_pays, per_gouts_autres)
		VALUES ('$nom','".$prenom."','".$code."','".$genre."','".$pays."','".$gouts."')";
		afficherObj($sql);
		$res = majDonneesPDO($conn,$sql);
		echo "Résultats de la requête ",$res . "<br/>";
		afficherObj($res);
	}
	else
		afficherObj("Le formulaire n'est pas complet");

	// Test de formulaire multi-tables
	if ( $erreur == false )
	{
		afficherObj($preference);

		// calcul du dernier per_num
		$sql = "select nvl(max(per_num),0) as maxi from personne";
		LireDonneesPDO2($conn,$sql,$donnee);  
		afficherObj($donnee);
		$per_num = $donnee[0]['MAXI']; // MAXI en majuscule
	
		foreach($preference as $cle=>$value )
		{
			$sql = "INSERT INTO preference VALUES ($per_num,'$value')";
			afficherObj($sql);
			$res = majDonneesPDO($conn,$sql);
			echo "Résultats de la requête ",$res . "<br/>";
		}
		
	}		

	$conn = null; // important
	?>
