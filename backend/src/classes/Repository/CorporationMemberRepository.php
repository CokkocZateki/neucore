<?php declare(strict_types=1);

namespace Neucore\Repository;

use Neucore\Entity\Corporation;
use Neucore\Entity\CorporationMember;
use Doctrine\Common\Collections\Criteria;

/**
 * @method CorporationMember|null find($id, $lockMode = null, $lockVersion = null)
 * @method CorporationMember[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CorporationMemberRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * @param int $corporationId EVE corporation ID
     * @param int $inactive Inactive for days
     * @param int $active Active within days
     * @return CorporationMember[]
     */
    public function findByLogonDate(int $corporationId, int $inactive = null, int $active = null): array
    {
        $criteria = new Criteria();
        $criteria
            ->where($criteria->expr()->eq('corporation', (new Corporation())->setId($corporationId)))
            ->orderBy(['logonDate' => 'DESC']);

        if ($active > 0) {
            $activeDate = date_create('now -'.$active.' days');
            $criteria->andWhere($criteria->expr()->gte('logonDate', $activeDate));
        }

        if ($inactive > 0) {
            $inactiveDate = date_create('now -'.$inactive.' days');
            $criteria->andWhere($criteria->expr()->lt('logonDate', $inactiveDate));
        }

        return $this->matching($criteria)->getValues();
    }
}
