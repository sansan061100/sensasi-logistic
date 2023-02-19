<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateMaterialInDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('material_in_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_in_id')
                ->constrained('material_ins')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('material_id')
                ->constrained('materials')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->integer('qty');
            $table->integer('price');
            $table->unique(['material_id', 'material_in_id']);
        });

        DB::unprepared('CREATE OR REPLACE PROCEDURE
            material_monthly_movements_upsert_in_procedure (
                IN materialID int,
                IN yearAt int,
                IN monthAt int
            )
            BEGIN
                INSERT INTO material_monthly_movements
                    (material_id, year, month, `in`, avg_in, avg_price)
                SELECT
                    mid.material_id,
                    yearAt,
                    monthAt,
                    @total_qty := SUM(mid.qty),
                    @avg_qty := AVG(mid.qty),
                    @avg_price := AVG(CASE WHEN price > 0 THEN price ELSE NULL END)
                FROM material_ins mi
                JOIN material_in_details mid ON mi.id = mid.material_in_id
                WHERE
                    mid.material_id = materialID AND
                    mid.qty > 0 AND
                    YEAR(mi.at) = yearAt AND
                    MONTH(mi.at) = monthAt
                GROUP BY mid.material_id
                ON DUPLICATE KEY UPDATE `in` = @total_qty, avg_in = @avg_qty, avg_price = @avg_price;
            END;
        ');

        DB::unprepared('CREATE OR REPLACE PROCEDURE material_in_details__material_monthly_movements_procedure (
                IN materialInID int,
                IN materialID int
            )
            BEGIN
                DECLARE yearAt int;
                DECLARE monthAt int;

                SELECT YEAR(`at`), MONTH(`at`) INTO yearAt, monthAt
                FROM material_ins
                WHERE id = materialInID;

                CALL material_monthly_movements_upsert_in_procedure(materialID, yearAt, monthAt);
            END;
        ');

        DB::unprepared('CREATE OR REPLACE TRIGGER material_ins_after_update_trigger
            AFTER UPDATE
            ON material_ins
            FOR EACH ROW
            BEGIN
                DECLARE done INT DEFAULT FALSE;
                DECLARE material_id INT;

                DECLARE cur CURSOR FOR SELECT material_id FROM material_in_details WHERE material_in_id = OLD.id;
                DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

                SET @is_at_changed = YEAR(NEW.at) <> YEAR(OLD.at) OR MONTH(NEW.at) <> MONTH(OLD.at);

                OPEN cur;

                read_loop: LOOP
                    FETCH cur INTO material_id;
                    IF done THEN
                        LEAVE read_loop;
                    END IF;

                    IF @is_at_changed THEN
                        CALL material_monthly_movements_upsert_in_procedure(material_id, YEAR(OLD.at), MONTH(OLD.at));
                    END IF;
                END LOOP;

                CLOSE cur;

            END;
        ');

        DB::unprepared('CREATE OR REPLACE TRIGGER material_in_details_after_insert_trigger
                AFTER INSERT
                ON material_in_details
                FOR EACH ROW
            BEGIN
                CALL material_in_details__material_monthly_movements_procedure(NEW.material_in_id, NEW.material_id);
            END;
        ');

        DB::unprepared('CREATE OR REPLACE TRIGGER material_in_details_after_update_trigger
                AFTER UPDATE
                ON material_in_details
                FOR EACH ROW
            BEGIN
                IF NEW.qty <> OLD.qty AND NEW.material_id = OLD.material_id THEN
                    CALL material_in_details__material_monthly_movements_procedure(NEW.material_in_id, NEW.material_id);
                END IF;

                IF NEW.material_id <> OLD.material_id THEN
                    CALL material_in_details__material_monthly_movements_procedure(NEW.material_in_id, OLD.material_id);
                    CALL material_in_details__material_monthly_movements_procedure(NEW.material_in_id, NEW.material_id);
                END IF;
            END;
        ');

        DB::unprepared('CREATE OR REPLACE TRIGGER material_in_details_after_delete_trigger
                AFTER DELETE
                ON material_in_details
                FOR EACH ROW
            BEGIN
                CALL material_in_details__material_monthly_movements_procedure(OLD.material_in_id, OLD.material_id);
            END;
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('material_in_details');
        DB::unprepared('DROP PROCEDURE IF EXISTS `material_monthly_movements_upsert_in_procedure`');
        DB::unprepared('DROP PROCEDURE IF EXISTS `material_in_details__material_monthly_movements_procedure`');
        DB::unprepared('DROP TRIGGER IF EXISTS material_ins_after_update_trigger');
        DB::unprepared('DROP TRIGGER IF EXISTS material_in_details_after_insert_trigger');
        DB::unprepared('DROP TRIGGER IF EXISTS material_in_details_after_update_trigger');
        DB::unprepared('DROP TRIGGER IF EXISTS material_in_details_after_delete_trigger');
    }
}
