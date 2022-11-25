<?php

declare(strict_types=1);

namespace OCA\Grauphel\Search;

use OCA\Grauphel\NoteStorage;
use OCA\Grauphel\AppInfo\Application;

use OCP\IL10N;
use OCP\IUser;
use OCP\IURLGenerator;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use OCP\Search\SearchResultEntry;

class Provider implements IProvider
{
        private IL10N $il10;
	private IURLGenerator $url;

	public function __construct(IL10N $l10n, IURLGenerator $urlGenerator)
        {
                $this->il10 = $l10n;
                $this->url = $urlGenerator;
        }

    public function getId(): string
    {   
        return Application::APP_ID;
    }

    public function getName(): string
    {   
        return $this->lutil->l10n->t('Grauphel');
    }

    public function getOrder(string $route, array $routeParameters): int
    {   
        if (strpos($route, $this->getId() . '.') === 0) {
            return -1;
        }

        return self::ORDER;
    }

    public function search(IUser $user, ISearchQuery $query) : SearchResult
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
            $res->link = $this->url->linkToRoute(
                'grauphel.gui.note', array('guid' => $row['note_guid'])
            );
            $results[] = $res;
        }
        return $results;
    }
}
?>
