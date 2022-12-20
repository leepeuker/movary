<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UpdateProductionCompanyForeignKeys extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE movie_production_company DROP CONSTRAINT movie_production_company_ibfk_1;
            ALTER TABLE movie_production_company DROP CONSTRAINT movie_production_company_ibfk_2;
            ALTER TABLE movie_production_company ADD CONSTRAINT movie_production_company_ibfk_1 FOREIGN KEY (company_id) REFERENCES company(id);
            ALTER TABLE movie_production_company ADD CONSTRAINT movie_production_company_ibfk_2 FOREIGN KEY (movie_id) REFERENCES movie(id);
            SQL
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE movie_production_company DROP CONSTRAINT movie_production_company_ibfk_1;
            ALTER TABLE movie_production_company DROP CONSTRAINT movie_production_company_ibfk_2;
            ALTER TABLE movie_production_company ADD CONSTRAINT movie_production_company_ibfk_1 FOREIGN KEY (company_id) REFERENCES company(id) ON DELETE CASCADE;
            ALTER TABLE movie_production_company ADD CONSTRAINT movie_production_company_ibfk_2 FOREIGN KEY (movie_id) REFERENCES movie(id) ON DELETE CASCADE;
            SQL
        );
    }
}
