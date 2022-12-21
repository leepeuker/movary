<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UpdateMoveForeignKeysToCascade extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE movie_crew DROP CONSTRAINT movie_crew_ibfk_2;
            ALTER TABLE movie_cast DROP CONSTRAINT movie_cast_ibfk_2;
            ALTER TABLE movie_production_company DROP CONSTRAINT movie_production_company_ibfk_2;
            ALTER TABLE movie_genre DROP CONSTRAINT movie_genre_ibfk_2;

            ALTER TABLE movie_crew ADD CONSTRAINT movie_crew_ibfk_2 FOREIGN KEY (movie_id) REFERENCES movie(id);
            ALTER TABLE movie_cast ADD CONSTRAINT movie_cast_ibfk_2 FOREIGN KEY (movie_id) REFERENCES movie(id);
            ALTER TABLE movie_production_company ADD CONSTRAINT movie_production_company_ibfk_2 FOREIGN KEY (movie_id) REFERENCES movie(id);
            ALTER TABLE movie_genre ADD CONSTRAINT movie_genre_ibfk_2 FOREIGN KEY (movie_id) REFERENCES movie(id);
            SQL,
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE movie_crew DROP CONSTRAINT movie_crew_ibfk_2;
            ALTER TABLE movie_cast DROP CONSTRAINT movie_cast_ibfk_2;
            ALTER TABLE movie_production_company DROP CONSTRAINT movie_production_company_ibfk_2;
            ALTER TABLE movie_genre DROP CONSTRAINT movie_genre_ibfk_2;

            ALTER TABLE movie_crew ADD CONSTRAINT movie_crew_ibfk_2 FOREIGN KEY (movie_id) REFERENCES movie(id) ON DELETE CASCADE;
            ALTER TABLE movie_cast ADD CONSTRAINT movie_cast_ibfk_2 FOREIGN KEY (movie_id) REFERENCES movie(id) ON DELETE CASCADE;
            ALTER TABLE movie_production_company ADD CONSTRAINT movie_production_company_ibfk_2 FOREIGN KEY (movie_id) REFERENCES movie(id) ON DELETE CASCADE;
            ALTER TABLE movie_genre ADD CONSTRAINT movie_genre_ibfk_2 FOREIGN KEY (movie_id) REFERENCES movie(id) ON DELETE CASCADE;
            SQL,
        );
    }
}
