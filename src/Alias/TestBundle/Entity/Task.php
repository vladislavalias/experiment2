<?php

namespace Alias\TestBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Task
 *
 * @ORM\Table()
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Task
{
  public function toArray()
  {
    $result = array();
    foreach ($this as $oneKey => $one)
    {
      $result[$oneKey] = $one;
    }
    
    return $result;
  }
  
  static $statusText = array(
    'start'   => 'Started',
    'abort'   => 'Aborted',
    'finish'  => 'Finished',
  );
  
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="category", type="string", length=255)
     */
    private $category;

    /**
     * @var string
     *
     * @ORM\Column(name="statement", type="text")
     */
    private $statement;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=50)
     */
    private $status;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updatedAt;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set category
     *
     * @param string $category
     * @return Task
     */
    public function setCategory($category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return string 
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set statement
     *
     * @param string $statement
     * @return Task
     */
    public function setStatement($statement)
    {
        $this->statement = $statement;

        return $this;
    }

    /**
     * Get statement
     *
     * @return string 
     */
    public function getStatement()
    {
        return $this->statement;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return Task
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Task
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return Task
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime 
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
    
  /**
   * @ORM\PrePersist
   */
  public function setCreatedAtValue()
  {
    $this->createdAt = new \DateTime();
  }
  
  /**
   * @ORM\PrePersist
   */
  public function setUpdatedAtValue()
  {
    $this->updatedAt = new \DateTime();
  }
  
  /**
   * @ORM\PrePersist
   */
  public function setStatementValue()
  {
    $this->statement = json_encode('');
  }
  
  
  /**
   * @ORM\PrePersist
   */
  public function setStatusValue()
  {
    $this->status = self::$statusText['start'];
  }
  
  /**
   * @ORM\PreUpdate
   */
  public function updateUpdatedAtValue()
  {
    $this->updatedAt = new \DateTime();
  }
}
