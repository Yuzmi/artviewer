<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ItemRepository")
 */
class Item
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     */
    private $url;

    /**
     * @ORM\Column(type="string", length=191, unique=true)
     */
    private $urlHash;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updateDate;

    /**
     * @ORM\Column(type="datetime")
     */
    private $publishedDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $parseDate;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $thumbnailUrl;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=191)
     */
    private $website;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isAdult;

    /**
     * @ORM\ManyToMany(targetEntity="Tag", inversedBy="items")
     * @ORM\JoinTable(name="items_tags")
     */
    private $tags;

    public function __construct()
    {
        $this->createDate = new \DateTime();
        $this->publishedDate = new \DateTime();
        $this->tags = new ArrayCollection();
    }

    public function getWebsitePicture() {
        if($this->website == "danbooru") {
            return "img/website/danbooru.png";
        } elseif($this->website == "konachan") {
            return "img/website/konachan.png";
        } elseif($this->website == "deviantart") {
            return "img/website/deviantart.png";
        } elseif($this->website == "safebooru") {
            return "img/website/safebooru.png";
        }
        return null;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;
        $this->urlHash = hash("sha1", $url);

        return $this;
    }

    public function getUrlHash(): ?string
    {
        return $this->urlHash;
    }

    public function setUrlHash(string $urlHash): self
    {
        $this->urlHash = $urlHash;

        return $this;
    }

    public function getCreateDate(): ?\DateTimeInterface
    {
        return $this->createDate;
    }

    public function setCreateDate(\DateTimeInterface $createDate): self
    {
        $this->createDate = $createDate;

        return $this;
    }

    public function getThumbnailUrl(): ?string
    {
        return $this->thumbnailUrl;
    }

    public function setThumbnailUrl(?string $thumbnailUrl): self
    {
        $this->thumbnailUrl = $thumbnailUrl;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return Collection|Tag[]
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags[] = $tag;
        }

        return $this;
    }

    public function removeTag(Tag $tag): self
    {
        if ($this->tags->contains($tag)) {
            $this->tags->removeElement($tag);
        }

        return $this;
    }

    public function getUpdateDate(): ?\DateTimeInterface
    {
        return $this->updateDate;
    }

    public function setUpdateDate(?\DateTimeInterface $updateDate): self
    {
        $this->updateDate = $updateDate;

        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(string $website): self
    {
        $this->website = $website;

        return $this;
    }

    public function getIsAdult(): ?bool
    {
        return $this->isAdult;
    }

    public function setIsAdult(bool $isAdult): self
    {
        $this->isAdult = $isAdult;

        return $this;
    }

    public function getPublishedDate(): ?\DateTimeInterface
    {
        return $this->publishedDate;
    }

    public function setPublishedDate(\DateTimeInterface $publishedDate): self
    {
        $this->publishedDate = $publishedDate;

        return $this;
    }

    public function getParseDate(): ?\DateTimeInterface
    {
        return $this->parseDate;
    }

    public function setParseDate(?\DateTimeInterface $parseDate): self
    {
        $this->parseDate = $parseDate;

        return $this;
    }
}
