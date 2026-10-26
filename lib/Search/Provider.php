<?php

declare(strict_types=1);

namespace OCA\Grauphel\Search;

use OCA\Grauphel\AppInfo\Application;
use OCA\Grauphel\Storage\NoteStorage;

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
    private IURLGenerator $urlGen;
    private NoteStorage $notes;

    public function __construct(
        IL10N $l10n, IURLGenerator $urlGen, NoteStorage $notes
    ) {
        $this->l10n   = $l10n;
        $this->urlGen = $urlGen;
        $this->notes  = $notes;
    }

    public function getId(): string
    {
        return Application::APP_ID;
    }

    public function getName(): string
    {
        return $this->l10n->t('Tomboy notes');
    }

    public function getOrder(string $route, array $routeParameters): int
    {
        if (strpos($route, $this->getId() . '.') === 0) {
            return -1;
        }
        return 50;
    }

    public function search(IUser $user, ISearchQuery $query): SearchResult
    {
        $notes = $this->notes;
        $notes->setUsername($user->getUID());

        $qp = new QueryParser();
        $rows = $notes->search($qp->parse($query->getTerm()));

        $results = array();
        foreach ($rows as $row) {
            $results[] = new SearchResultEntry(
                '',
                htmlspecialchars_decode($row['note_title'], ENT_QUOTES | ENT_HTML5),
                '',
                $this->urlGen->linkToRoute(
                    'grauphel.gui.note', array('guid' => $row['note_guid'])
                ),
                $this->urlGen->imagePath('grauphel', 'app.svg')
            );
        }
        return SearchResult::complete($this->getName(), $results);
    }
}
