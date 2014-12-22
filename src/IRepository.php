<?php
namespace Vda\Datasource;

use Vda\Query\Delete;
use Vda\Query\Insert;
use Vda\Query\Update;
use Vda\Query\Upsert;

interface IRepository extends IDatasource
{
    /**
     * @param Insert $insert
     * @return integer Number of affected rows
     */
    public function insert(Insert $insert);

    /**
     * @param Upsert $Upsert
     * @return integer Number of affected rows
     */
    public function upsert(Upsert $upsert);

    /**
     * @param Update $update
     * @return integer Number of affected rows
     */
    public function update(Update $update);

    /**
     * @param Delete $delete
     * @return integer Number of affected rows
     */
    public function delete(Delete $delete);

    /**
     * @return integer Value of autoincrement field if available
     */
    public function getLastInsertId();
}
