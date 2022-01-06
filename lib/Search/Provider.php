<?php
namespace OCA\Grauphel\Search;

use \OCA\Grauphel\Lib\NoteStorage;
use \OCA\Grauphel\AppInfo\Application;
use \OCP\IL10N;
use \OCP\IURLGenerator;
use \OCP\IUser;
use \OCP\Search\IProvider;
use \OCP\Search\SearchResult;
use \OCP\Search\SearchResultEntry;
use \OCP\Search\ISearchQuery;

class Provider implements IProvider
{
    private $l10n;
    private $urlGen;

    public function __construct(IL10N $l10n, IURLGenerator $urlGenerator)
    {   
        $this->l10n = $l10n;
        $this->urlGen = $urlGenerator;
    }

    public function getId(): string
    {   
        return Application::APP_ID;
    }

    public function getName(): string
    {   
        return $this->l->t('Grauphel');
    }

    public function getOrder(string $route, array $routeParameters): int
    {   
        if (strpos($route, Application::APP_ID . '.') === 0) {
            return -1;
        }

        return self::ORDER;
    }

    public function search(OCP\IUser $user, OCP\Search\ISearchQuery $query)
    {
        $notes  = new NoteStorage($this->urlGen);
        $notes->setUsername( $user->getUID());
        
	$qp = new QueryParser();
        $rows = $notes->search($qp->parse($query));

        $results = array();
        foreach ($rows as $row) 
	{
            $res = new Note();
            $res->id   = $row['note_guid'];
            $res->name = htmlspecialchars_decode($row['note_title']);
            $res->link = $this->urlGen->linkToRoute(
                'grauphel.gui.note', array('guid' => $row['note_guid'])
            );
            $results[] = $res;
        }
        return $results;
    }
}
?>
