<?php

namespace Asciisd\Kashier\Common;

use Asciisd\Kashier\Exception\KashierConfigurationException;

/**
 * Class ReflectionUtil
 *
 * @package Asciisd\Kashier\Common
 */
class ReflectionUtil
{
    /**
     * Reflection Methods
     *
     * @var \ReflectionMethod[]
     */
    private static array $propertiesRefl = array();

    /**
     * Properties Type
     *
     * @var string[]
     */
    private static array $propertiesType = array();


    /**
     * Gets Property Class of the given property.
     * If the class is null, it returns null.
     * If the property is not found, it returns null.
     *
     * @param $class
     * @param $propertyName
     * @return null|string
     * @throws KashierConfigurationException
     */
    public static function getPropertyClass($class, $propertyName): ?string
    {
        if ($class === get_class(new KashierModel())) {
            // Make it generic if KashierModel is used for generating this
            return get_class(new KashierModel());
        }

        // If the class doesn't exist, or the method doesn't exist, return null.
        if (! class_exists($class) || ! method_exists($class, self::getter($class, $propertyName))) {
            return null;
        }

        if (($annotations = self::propertyAnnotations($class, $propertyName)) && isset($annotations['return'])) {
            $param = $annotations['return'];
        }

        if (isset($param)) {
            $anno = preg_split("/[\s\[\]]+/", $param);
            return $anno[0];
        }

        throw new KashierConfigurationException("Getter function for '$propertyName' in '$class' class should have a proper return type.");
    }

    /**
     * Returns the properly formatted getter function name based on class name and property
     * Formats the property name to a standard getter function
     *
     * @param string $class
     * @param string $propertyName
     * @return string getter function name
     */
    public static function getter(string $class, string $propertyName): string
    {
        return method_exists($class, 'get'.ucfirst($propertyName)) ?
            'get'.ucfirst($propertyName) :
            'get'.preg_replace_callback("/([_\-\s]?([a-z0-9]+))/", 'self::replace_callback', $propertyName);
    }

    /**
     * Retrieves Annotations of each property
     *
     * @param $class
     * @param $propertyName
     * @return mixed
     */
    public static function propertyAnnotations($class, $propertyName)
    {
        $class = is_object($class) ? get_class($class) : $class;
        if (! class_exists('ReflectionProperty')) {
            throw new \RuntimeException('Property type of '.$class."::{$propertyName} cannot be resolved");
        }

        if ($annotations = & self::$propertiesType[$class][$propertyName]) {
            return $annotations;
        }

        if (! ($refl = & self::$propertiesRefl[$class][$propertyName])) {
            $getter = self::getter($class, $propertyName);
            try {
                $refl = new \ReflectionMethod($class, $getter);
                self::$propertiesRefl[$class][$propertyName] = $refl;
            } catch (\ReflectionException $e) {
            }
        }

        if (! preg_match_all(
            '~\@([^\s@\(]+)[\t ]*(?:\(?([^\n@]+)\)?)?~i',
            $refl->getDocComment(),
            $annots
        )) {
            return null;
        }
        foreach ($annots[1] as $i => $annot) {
            $annotations[strtolower($annot)] = empty($annots[2][$i]) ? true : rtrim($annots[2][$i], " \t\n\r)");
        }

        return $annotations;
    }

    /**
     * Checks if the Property is of type array or an object
     *
     * @param $class
     * @param $propertyName
     * @return null|boolean
     * @throws KashierConfigurationException
     */
    public static function isPropertyClassArray($class, $propertyName): ?bool
    {
        // If the class doesn't exist, or the method doesn't exist, return null.
        if (! class_exists($class) || ! method_exists($class, self::getter($class, $propertyName))) {
            return null;
        }

        if (($annotations = self::propertyAnnotations($class, $propertyName)) && isset($annotations['return'])) {
            $param = $annotations['return'];
        }

        if (isset($param)) {
            return substr($param, -strlen('[]')) === '[]';
        }

        throw new KashierConfigurationException("Getter function for '$propertyName' in '$class' class should have a proper return type.");
    }

    /**
     * preg_replace_callback callback function
     *
     * @param $match
     * @return string
     */
    private static function replace_callback($match): string
    {
        return ucwords($match[2]);
    }
}
