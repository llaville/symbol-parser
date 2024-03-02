<?php
final class Article
{
    public const DELETED = 'deleted';
    public const PUBLISHED = 'published';
    public const DRAFT = 'draft';
    public const ALL = [
        self::DELETED,
        self::PUBLISHED,
        self::DRAFT,
    ];
}

use Symfony\Component\Validator\Constraints as Assert;

final class ArticleDto
{
    /**
     * @Assert\Choice(choices=Article::ALL, message="Invalid status")
     */
    private $status;
}

