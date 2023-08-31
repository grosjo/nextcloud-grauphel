<?php

declare(strict_types=1);

namespace OCA\Grauphel\Search;

use OCA\Grauphel\Storage\NoteStorage;
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
    private IL10N $l10n;
    private IURLGenerator $url;

    public function __construct(IL10N $l10n, IURLGenerator $urlGenerator)
    {
        $this->l10n = $l10n;
        $this->url = $urlGenerator;
    }

    public function getId(): string
    {
        return Application::APP_ID;
    }

    public function getName(): string
    {
        return $this->l10n->t('Grauphel');
    }

    public function getOrder(string $route, array $routeParameters): int
    {
        if (strpos($route, $this->getId() . '.') === 0) {
            return -1;
        }

        return 55;
    }

    public function search(IUser $user, ISearchQuery $query) : SearchResult
    {
        $notes  = new NoteStorage($this->url);
        $notes->setUsername($user->getUID());

        $qp = new QueryParser();
        $rows = $notes->search($qp->parse($query->getTerm()));

        $icon = $this->url->imagePath($this->getID(), 'app.svg');

        $results = array();
        foreach ($rows as $row)
        {
            $res = new SearchResultEntry(
                $icon,
                htmlspecialchars_decode($row['note_title']),
                '',
                $this->url->linkToRoute(
                    'grauphel.gui.note', array('guid' => $row['note_guid'])
                )
            );
            $results[] = $res;
        }

        return SearchResult::complete(
            $this->getName(),
            $results
        );
    }
}
?>
