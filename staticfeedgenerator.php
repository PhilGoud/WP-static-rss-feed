<?php

// Définition des constantes
$url = 'https://your/original/feed/for/your/podcast/rss.xml';
$destinationFile = __DIR__ . '/rss.xml';
$lastModified = file_exists($destinationFile) ? date('d/m/Y H:i:s', filemtime($destinationFile)) : 'Jamais';
$updateMessage = '';

// Fonction pour extraire les informations du dernier épisode
function getLatestEpisodeInfo($filePath) {
    if (!file_exists($filePath)) {
        return null;
    }

    $xmlContent = simplexml_load_file($filePath);
    if ($xmlContent === false) {
        return null;
    }

    $latestEpisode = $xmlContent->channel->item[0] ?? null;
    if (!$latestEpisode) {
        return null;
    }

    $title = (string) $latestEpisode->title;
    $pubDate = (string) $latestEpisode->pubDate;
    $image = (string) ($latestEpisode->children('itunes', true)->image->attributes()->href ?? '');
    $duration = (string) ($latestEpisode->children('itunes', true)->duration ?? '');
    $mediaUrl = (string) ($latestEpisode->enclosure['url'] ?? '');
    $link = (string) $latestEpisode->link;

    return [
        'title' => $title,
        'pubDate' => $pubDate,
        'image' => $image,
        'duration' => $duration,
        'mediaUrl' => $mediaUrl,
        'link' => $link
    ];
}

// Fonction pour modifier le contenu XML
function replaceAtomLink($content) {
    $search = '<atom:link href="https://asoundmr.com/feed/podcast/" rel="self" type="application/rss+xml" />';
    $replace = '<atom:link href="https://asoundmr.com/rss.xml" rel="self" type="application/rss+xml" />';
    return str_replace($search, $replace, $content);
}

// Gestion du rafraîchissement
if (isset($_POST['refresh'])) {
    try {
        $remoteContent = file_get_contents($url);

        if ($remoteContent === false) {
            throw new Exception("Impossible de récupérer le contenu de l'URL.");
        }

        // Remplace la ligne souhaitée
        $modifiedContent = replaceAtomLink($remoteContent);

        // Vérifie si le contenu a changé
        $localContent = file_exists($destinationFile) ? file_get_contents($destinationFile) : '';

        if ($modifiedContent !== $localContent) {
            file_put_contents($destinationFile, $modifiedContent);
            $updateMessage = "Le flux a été mis à jour avec succès.";
        } else {
            $updateMessage = "Le flux est déjà à jour.";
        }

    } catch (Exception $e) {
        $updateMessage = "Erreur : " . $e->getMessage();
    }

    // Rafraîchit la date de dernière modification
    $lastModified = file_exists($destinationFile) ? date('d/m/Y H:i:s', filemtime($destinationFile)) : 'Jamais';
}

$latestEpisode = getLatestEpisodeInfo($destinationFile);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion du flux statique</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f9f9f9;
            color: #333;
            margin: 0;
            padding: 0 20px;
            line-height: 1.6;
        }
        h1 {
            color: #0078D7;
            text-align: center;
            margin-top: 20px;
        }
        p {
            margin: 10px 0;
        }
        form {
            text-align: center;
            margin-top: 20px;
        }
        button {
            background-color: #0078D7;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #005fa3;
        }
        img {
            max-width: 300px;
            display: block;
            margin: 10px auto;
        }
        .episode-info {
            background-color: #ffffff;
            padding: 20px;
            margin: 20px auto;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            max-width: 600px;
        }
        a {
            color: #0078D7;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h1>Gestion du flux statique</h1>

    <form method="post">
        <button type="submit" name="refresh">Rafraîchir le flux</button>
    </form>

    <?php if ($latestEpisode): ?>
        <div class="episode-info">
          <p><strong>Date de dernière mise à jour :</strong> <?php echo $lastModified; ?></p>

    		<?php if (!empty($updateMessage)): ?>
       			 <p style="color: green;"><strong><?php echo $updateMessage; ?></strong></p>
    		<?php endif; ?>
          
          	<?php if (file_exists($destinationFile)): ?>
        		<p><a href="rss.xml" target="_blank"> Consulter le flux</a></p>
            <?php else: ?>
                <p>Aucun flux disponible.</p>
            <?php endif; ?>
          	<hr>
            <h2>Dernier épisode :</h2>
            <p><strong>Titre :</strong> <a href="<?php echo htmlspecialchars($latestEpisode['link']); ?>" target="_blank"><?php echo htmlspecialchars($latestEpisode['title']); ?></a></p>
            <p><strong>Date de publication :</strong> <?php echo htmlspecialchars($latestEpisode['pubDate']); ?></p>
            <p><strong>Durée :</strong> <?php echo htmlspecialchars($latestEpisode['duration']); ?></p>
            <?php if (!empty($latestEpisode['image'])): ?>
                <img src="<?php echo htmlspecialchars($latestEpisode['image']); ?>" alt="Pochette de l'épisode">
            <?php endif; ?>
            <?php if (!empty($latestEpisode['mediaUrl'])): ?>
                <p><strong>Média :</strong> <a href="<?php echo htmlspecialchars($latestEpisode['mediaUrl']); ?>" target="_blank">Écouter l'épisode</a></p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <p>Aucun épisode disponible pour le moment.</p>
    <?php endif; ?>
</body>
</html>
