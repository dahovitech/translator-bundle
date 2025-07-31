<?php

namespace Dahovitech\TranslatorBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'Dahovitech\TranslatorBundle\Repository\TranslationVersionRepository')]
#[ORM\Table(name: 'dahovitech_translation_versions')]
#[ORM\Index(columns: ['translation_key', 'locale', 'domain'], name: 'idx_translation_version_key_locale_domain')]
#[ORM\Index(columns: ['created_at'], name: 'idx_translation_version_created_at')]
#[ORM\Index(columns: ['version_number'], name: 'idx_translation_version_number')]
class TranslationVersion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $translationKey;

    #[ORM\Column(type: 'string', length: 10)]
    private string $locale;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'string', length: 100, options: ['default' => 'messages'])]
    private string $domain = 'messages';

    #[ORM\Column(type: 'integer')]
    private int $versionNumber;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $author = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $changeComment = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $changeType; // 'create', 'update', 'delete'

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $previousContent = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'string', length: 32, nullable: true)]
    private ?string $contentHash = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->versionNumber = 1;
        $this->changeType = 'create';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTranslationKey(): string
    {
        return $this->translationKey;
    }

    public function setTranslationKey(string $translationKey): self
    {
        $this->translationKey = $translationKey;
        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): self
    {
        $this->locale = $locale;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        $this->contentHash = md5($content);
        return $this;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function setDomain(string $domain): self
    {
        $this->domain = $domain;
        return $this;
    }

    public function getVersionNumber(): int
    {
        return $this->versionNumber;
    }

    public function setVersionNumber(int $versionNumber): self
    {
        $this->versionNumber = $versionNumber;
        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(?string $author): self
    {
        $this->author = $author;
        return $this;
    }

    public function getChangeComment(): ?string
    {
        return $this->changeComment;
    }

    public function setChangeComment(?string $changeComment): self
    {
        $this->changeComment = $changeComment;
        return $this;
    }

    public function getChangeType(): string
    {
        return $this->changeType;
    }

    public function setChangeType(string $changeType): self
    {
        if (!in_array($changeType, ['create', 'update', 'delete'])) {
            throw new \InvalidArgumentException('Invalid change type. Must be one of: create, update, delete');
        }
        $this->changeType = $changeType;
        return $this;
    }

    public function getPreviousContent(): ?string
    {
        return $this->previousContent;
    }

    public function setPreviousContent(?string $previousContent): self
    {
        $this->previousContent = $previousContent;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function addMetadata(string $key, $value): self
    {
        if ($this->metadata === null) {
            $this->metadata = [];
        }
        $this->metadata[$key] = $value;
        return $this;
    }

    public function getMetadataValue(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }

    public function getContentHash(): ?string
    {
        return $this->contentHash;
    }

    public function setContentHash(?string $contentHash): self
    {
        $this->contentHash = $contentHash;
        return $this;
    }

    /**
     * Vérifie si cette version a le même contenu qu'une autre
     */
    public function hasSameContentAs(TranslationVersion $other): bool
    {
        return $this->contentHash === $other->getContentHash();
    }

    /**
     * Calcule la différence avec une autre version
     */
    public function getDiffWith(TranslationVersion $other): array
    {
        $diff = [
            'content_changed' => $this->content !== $other->getContent(),
            'author_changed' => $this->author !== $other->getAuthor(),
            'domain_changed' => $this->domain !== $other->getDomain(),
            'changes' => []
        ];

        if ($diff['content_changed']) {
            $diff['changes']['content'] = [
                'from' => $other->getContent(),
                'to' => $this->content
            ];
        }

        if ($diff['author_changed']) {
            $diff['changes']['author'] = [
                'from' => $other->getAuthor(),
                'to' => $this->author
            ];
        }

        if ($diff['domain_changed']) {
            $diff['changes']['domain'] = [
                'from' => $other->getDomain(),
                'to' => $this->domain
            ];
        }

        return $diff;
    }

    /**
     * Retourne une représentation textuelle de la version
     */
    public function __toString(): string
    {
        return sprintf(
            'Version %d of %s (%s/%s) by %s at %s',
            $this->versionNumber,
            $this->translationKey,
            $this->locale,
            $this->domain,
            $this->author ?? 'unknown',
            $this->createdAt->format('Y-m-d H:i:s')
        );
    }

    /**
     * Crée une nouvelle version basée sur une traduction existante
     */
    public static function createFromTranslation(Translation $translation, string $changeType = 'update', ?string $author = null, ?string $comment = null): self
    {
        $version = new self();
        $version->setTranslationKey($translation->getTranslationKey());
        $version->setLocale($translation->getLocale());
        $version->setContent($translation->getContent());
        $version->setDomain($translation->getDomain());
        $version->setChangeType($changeType);
        $version->setAuthor($author);
        $version->setChangeComment($comment);

        return $version;
    }

    /**
     * Retourne les types de changements disponibles
     */
    public static function getChangeTypes(): array
    {
        return ['create', 'update', 'delete'];
    }

    /**
     * Retourne une description lisible du type de changement
     */
    public function getChangeTypeDescription(): string
    {
        return match ($this->changeType) {
            'create' => 'Création',
            'update' => 'Modification',
            'delete' => 'Suppression',
            default => 'Inconnu'
        };
    }

    /**
     * Vérifie si cette version représente une création
     */
    public function isCreation(): bool
    {
        return $this->changeType === 'create';
    }

    /**
     * Vérifie si cette version représente une modification
     */
    public function isUpdate(): bool
    {
        return $this->changeType === 'update';
    }

    /**
     * Vérifie si cette version représente une suppression
     */
    public function isDeletion(): bool
    {
        return $this->changeType === 'delete';
    }
}

