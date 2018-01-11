<?php

use Composer\Semver\Comparator;
use Composer\Semver\Semver;

function output($string) {
    echo $string . "\n";
}

function url($text, $url) {
    if (empty($url)) {
        return $text;
    }
    return "[$text]($url)";
}

function image($alttext, $screenshots) {
    $text = "[details=\"Screenshots\"]";
    foreach ($screenshots as $screenshot) {
        $text .= "![$alttext]($screenshot)";
    }
    $text .= "[/details]";
    return $text;
}

function heading($string) {
    return "## $string";
}

function emphasis($string) {
    return "*$string*";
}

function formatDate($datestring) {
    return date("Y-m-d", strtotime($datestring));
}

require_once "vendor/autoload.php";

$json = file_get_contents("https://plugins.matomo.org/api/2.0/plugins");
$piwikVersion = trim(file_get_contents("https://api.piwik.org/1.0/getLatestVersion/"));
//$json = file_get_contents("plugins.json");
//$piwikVersion = "3.2.1";

$data = json_decode($json);
foreach ($data->plugins as $plugin) {
    $add = false;
    $maxVersion = "0.0.1";
    $maxRelease = false;
    foreach ($plugin->versions as $version) {
        if (Comparator::greaterThanOrEqualTo($version->name, $maxVersion)) {
            $maxVersion = $version->name;
            $maxRelease = $version;
        }
    }
    $latestVersion = $maxRelease;
    if (empty($latestVersion->requires) or empty($latestVersion->requires->piwik)) {
        $add = "Probably not (hasn't specified supported versions)";
    } else {
        $contstraint = $latestVersion->requires->piwik;
        if (Semver::satisfies($piwikVersion, $latestVersion->requires->piwik)) {
            if (strpos($contstraint, "3") === false && strpos($contstraint, "4") === false) {
                $add = "Possible ($contstraint)";
            }
        } else {
            $add = "No ($contstraint)";
        }
    }
    if ($add !== false) {
        output(heading($plugin->displayName));
        output(emphasis($plugin->description));
        output(url("Marketplace", "https://plugins.matomo.org/" . $plugin->name) . ", " . \
                url("repository", $plugin->repositoryUrl));
        output("Matomo 3 support: " . $add);
        $authors = [];
        foreach ($plugin->authors as $author) {
            $authors[] = url($author->name, $author->homepage);
        }
        output("Author: " . join(", ", $authors));
        output("Latest Release: " . formatDate($latestVersion->release));
        output(url("License: " . $latestVersion->license->name, $latestVersion->license->url));
        $activity = $plugin->activity;
        output(sprintf("%s commits by %s contributors | last commit was on %s",
            $activity->numCommits, $activity->numContributors, formatDate($activity->lastCommitDate)));
        output("Downloads: " . $plugin->numDownloads);
        if (!empty($plugin->screenshots)) {
            output(image($plugin->name, $plugin->screenshots));
        }
        echo "\n";

    }
}