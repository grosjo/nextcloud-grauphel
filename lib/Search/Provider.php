<?php

declare(strict_types=1);

namespace OCA\Grauphel\Search;

use OCA\Grauphel\AppInfo\Application;
use OCA\Grauphel\Search\QueryParser;
use OCA\Grauphel\Storage\NoteStorage;

use OCP\IL10N;
use OCP\IDBConnection;
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
    private IDBConnection $db;

    public function __construct(IL10N $l10n, IURLGenerator $urlGenerator, IDBConnection $db)
    {
        $this->l10n = $l10n;
        $this->url = $urlGenerator;
        $this->db = $db;
    }

    public function getId(): string
    {
        return Application::APP_ID;
    }

    public function getName(): string
    {
        return $this->l10n->t('Grauphel');
    }

    public function getOrder(string $route, array $routeParameters): ?int
    {
        if (strpos($route, $this->getId() . '.') === 0) {
            return -1;
        }

        return self::ORDER;
    }

    public function search(IUser $user, ISearchQuery $query): SearchResult
    {
        $notes = new NoteStorage($this->url, $this->db);
        $notes->setUsername($user->getUID());

        $qp = new QueryParser();
        $rows = $notes->search($qp->parse($query));

        $results = [];
        foreach ($rows as $row) {
            $results[] = new SearchResultEntry(
                '',
                htmlspecialchars_decode($row['note_title']),
                '',
                $this->url->linkToRoute(
                    'grauphel.gui.note', array('guid' => $row['note_guid'])
                ),
                '',
                false
            );
        }
        return SearchResult::complete($this->getName(), $results);
    }
}
?>
