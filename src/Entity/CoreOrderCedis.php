<?php

namespace Adteam\Core\Credits\Result\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CoreOrderCedis
 *
 * @ORM\Table(name="core_order_cedis", indexes={@ORM\Index(name="core_order_cedis_ibfk_1", columns={"cedis_id"}), @ORM\Index(name="core_order_cedis_ibfk_2", columns={"order_id"})})
 * @ORM\Entity
 */
class CoreOrderCedis
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Adteam\Core\Credits\Result\Entity\CoreCedis
     *
     * @ORM\ManyToOne(targetEntity="Adteam\Core\Credits\Result\Entity\CoreCedis")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="cedis_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $cedis;

    /**
     * @var \Adteam\Core\Credits\Result\Entity\CoreOrders
     *
     * @ORM\ManyToOne(targetEntity="Adteam\Core\Credits\Result\Entity\CoreOrders")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="order_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $order;


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
     * Set cedis
     *
     * @param \Adteam\Core\Credits\Result\Entity\CoreCedis $cedis
     *
     * @return CoreOrderCedis
     */
    public function setCedis(\Adteam\Core\Credits\Result\Entity\CoreCedis $cedis = null)
    {
        $this->cedis = $cedis;

        return $this;
    }

    /**
     * Get cedis
     *
     * @return \Adteam\Core\Credits\Result\Entity\CoreCedis
     */
    public function getCedis()
    {
        return $this->cedis;
    }

    /**
     * Set order
     *
     * @param \Adteam\Core\Credits\Result\Entity\CoreOrders $order
     *
     * @return CoreOrderCedis
     */
    public function setOrder(\Adteam\Core\Credits\Result\Entity\CoreOrders $order = null)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Get order
     *
     * @return \Adteam\Core\Credits\Result\Entity\CoreOrders
     */
    public function getOrder()
    {
        return $this->order;
    }
}

