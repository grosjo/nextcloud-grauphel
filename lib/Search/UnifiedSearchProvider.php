<?php

declare(strict_types=1);

namespace OCA\Grauphel\Search;

use OCA\Grauphel\AppInfo\Application;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Search\IProvider;
use OCP\Search\SearchResult;
use OCP\Search\SearchResultEntry;
use OCP\Search\ISearchQuery;

class Provider implements IProvider 
{
    private $l10n;
    private $urlGenerator;

    public function __construct(IL10N $l10n, IURLGenerator $urlGenerator)
    {
	$this->l10n = $l10n;
	$this->urlGenerator = $urlGenerator;
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

    public function search(IUser $user, ISearchQuery $query): SearchResult 
    {
	$s = trim($query->getTerm());
	$kw0 = split(" ",$s);
	$kw=array();
	foreach($kw0 as $w)
	{
		$w=trim($w);
		if(strlen($w)>0) $kw[]=$w;
	}

	$offset = ($query->getCursor() ?? 0);
	$limit = $query->getLimit();

	$notes  = new \OCA\Grauphel\Lib\NoteStorage($this->urlGenerator);
	$notes->setUsername($user->getUid());
	$last = end($notes);

	return SearchResult::paginated( $this->l10n->t('grauphel'), $notes, $last->getCreatedAt() );
    }
}
