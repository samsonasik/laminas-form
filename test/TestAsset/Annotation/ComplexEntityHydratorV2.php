<?php

/**
 * @see       https://github.com/laminas/laminas-form for the canonical source repository
 * @copyright https://github.com/laminas/laminas-form/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-form/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Form\TestAsset\Annotation;

use Laminas\Form\Annotation;

/**
 * @Annotation\Name("user")
 * @Annotation\Attributes({"legend":"Register"})
 * @Annotation\Hydrator("Laminas\Hydrator\ObjectProperty")
 */
class ComplexEntityHydratorV2
{
    /**
     * @Annotation\ErrorMessage("Invalid or missing username")
     * @Annotation\Filter({"name":"StringTrim"})
     * @Annotation\Validator({"name":"NotEmpty"})
     * @Annotation\Validator({"name":"StringLength","options":{"min":3,"max":25}})
     */
    public $username;

    /**
     * @Annotation\Attributes({"type":"password","label":"Enter your password"})
     * @Annotation\Filter({"name":"StringTrim"})
     * @Annotation\Validator({"name":"StringLength","options":{"min":3}})
     */
    public $password;

    /**
     * @Annotation\Flags({"priority":100})
     * @Annotation\Filter({"name":"StringTrim"})
     * @Annotation\Validator({"name":"EmailAddress","options":{"allow":15}})
     * @Annotation\Attributes({"type":"email","label":"What is the best email to reach you at?"})
     */
    public $email;

    /**
     * @Annotation\Name("user_image")
     * @Annotation\AllowEmpty()
     * @Annotation\Required(false)
     * @Annotation\Attributes({"type":"text","label":"Provide a URL for your avatar (optional):"})
     * @Annotation\Validator({"name":"LaminasTest\Form\TestAsset\Annotation\UrlValidator"})
     */
    public $avatar;

    /**
     * @Annotation\Exclude()
     */
    protected $someComposedObject;
}
