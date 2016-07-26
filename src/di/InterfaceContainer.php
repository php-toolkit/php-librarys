<?php
/**
 * Use : this
 * Date : 2015-1-10
 * InterfaceContainer.php
 */
namespace inhere\library\di;

interface InterfaceContainer
{
    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws \NotFoundException  No entry was found for this identifier.
     * @throws ContainerException Error while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get($id);

    /**
     * @return boolean
     */
    public function has($id);
}