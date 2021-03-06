<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */

namespace BackBee\Workflow;

use BackBee\Exception\InvalidArgumentException;
use BackBee\Security\Acl\Domain\AbstractObjectIdentifiable;
use BackBee\Site\Layout;
use BackBee\Utils\Numeric;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Security\Core\Util\ClassUtils;

/**
 * A workflow state for NestedNode\Page.
 *
 * A negative code state is applied before online main state
 * A positive code state is applied after online main state
 *
 * A state can be associated to a specific Site\Layout and/or Listener
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 * @ORM\Entity(repositoryClass="BackBee\Workflow\Repository\StateRepository")
 * @ORM\Table(name="workflow")
 *
 * @Serializer\ExclusionPolicy("all")
 */
class State extends AbstractObjectIdentifiable implements \JsonSerializable
{
    /**
     * The unique identifier of the state.
     *
     * @var string
     * @ORM\Id
     * @ORM\Column(type="string", name="uid")
     *
     * @Serializer\Expose
     * @Serializer\Type("string")
     * @Serializer\ReadOnly
     */
    protected $_uid;

    /**
     * The code of the workflow state.
     *
     * @var int
     * @ORM\Column(type="integer", name="code")
     *
     * @Serializer\Expose
     * @Serializer\Type("integer")
     */
    protected $_code;

    /**
     * The label of the workflow state.
     *
     * @var string
     * @ORM\Column(type="string", name="label")
     *
     * @Serializer\Expose
     * @Serializer\Type("string")
     */
    protected $_label;

    /**
     * The optional layout to be applied for state.
     *
     * @var \BackBee\Site\Layout
     * @ORM\ManyToOne(targetEntity="BackBee\Site\Layout", inversedBy="_states", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="layout", referencedColumnName="uid")
     */
    protected $_layout;

    /**
     * The optional listener classname.
     *
     * @var string
     * @ORM\Column(type="string", name="listener")
     */
    protected $_listener;

    /**
     * State's constructor.
     *
     * @param string $uid
     * @param array  $options
     */
    public function __construct($uid = null, array $options = array())
    {
        $this->_uid = (null === $uid) ? md5(uniqid('', true)) : $uid;

        if (true === array_key_exists('code', $options)) {
            $this->setCode($options['code']);
        }

        if (true === array_key_exists('label', $options)) {
            $this->setLabel($options['label']);
        }
    }

    /**
     * Returns the unique identifier.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getUid()
    {
        return $this->_uid;
    }

    /**
     * Returns the code of the state.
     *
     * @return int
     * @codeCoverageIgnore
     */
    public function getCode()
    {
        return $this->_code;
    }

    /**
     * Sets the code.
     *
     * @param int $code
     *
     * @return \BackBee\Workflow\State
     *
     * @throws \BackBee\Exception\InvalidArgumentException
     */
    public function setCode($code)
    {
        if (false === Numeric::isInteger($code)) {
            throw new InvalidArgumentException('The code of a workflow state has to be an integer');
        }

        $this->_code = $code;

        return $this;
    }

    /**
     * Returns the label.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getLabel()
    {
        return $this->_label;
    }

    /**
     * Returns the layout if defined, NULL otherwise.
     *
     * @return \BackBee\Site\Layout
     * @codeCoverageIgnore
     */
    public function getLayout()
    {
        return $this->_layout;
    }

    /**
     * Returns the listener classname if defined, NULL otherwise.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getListener()
    {
        return $this->_listener;
    }

    /**
     * Sets the label.
     *
     * @param type $label
     *
     * @return \BackBee\Workflow\State
     * @codeCoverageIgnore
     */
    public function setLabel($label)
    {
        $this->_label = strval($label);

        return $this;
    }

    /**
     * Sets the layout associated to this state.
     *
     * @param \BackBee\Site\Layout $layout
     *
     * @return \BackBee\Workflow\State
     */
    public function setLayout(Layout $layout = null)
    {
        $this->_layout = $layout;

        return $this;
    }

    /**
     * Sets the optional listener classname.
     *
     * @param string $listener
     *
     * @return \BackBee\Workflow\State
     * @codeCoverageIgnore
     */
    public function setListener($listener = null)
    {
        $this->_listener = $listener;

        return $this;
    }

    /**
     * Layout's uid getter.
     *
     * @return null|string
     *
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("layout_uid")
     */
    public function getLayoutUid()
    {
        return null !== $this->getLayout() ? $this->getLayout()->getUid() : null;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            'uid'        => $this->getUid(),
            'layout_uid' => null !== $this->getLayout() ? $this->getLayout()->getUid() : null,
            'code'       => $this->getCode(),
            'label'      => $this->getLabel(),
        ];
    }
}
