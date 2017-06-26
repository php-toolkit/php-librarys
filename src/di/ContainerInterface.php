<?php
/**
 * Use : this
 * Date : 2015-1-10
 * InterfaceContainer.php
 */

namespace inhere\library\di;

/**
 * Interface InterfaceContainer
 * @package inhere\library\di
 */
interface ContainerInterface
{
    /**
     * Finds an entry of the container by its identifier and returns it.
     * @param string $id Identifier of the entry to look for.
     * @return mixed No entry was found for this identifier.
     */
    public function get($id);

    /**
     * @param string $id
     * @return bool
     */
    public function has($id);
}
