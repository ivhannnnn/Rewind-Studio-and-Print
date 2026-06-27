<?php
session_start();
include("db.php");

/* =========================================
   FETCH REVIEWS
========================================= */
$query = "
    SELECT 
        r.*,
        u.full_name
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC
";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer Reviews</title>

<link rel="stylesheet" href="reviews.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<div class="overlay"></div>

<div class="reviews-container">

    <h1>Customer Reviews</h1>
    <p class="subtitle">See what our clients say about Rewind Studio & Prints</p>

    <?php if(mysqli_num_rows($result) > 0): ?>

        <div class="reviews-grid">

            <?php while($row = mysqli_fetch_assoc($result)): ?>

                <div class="review-card">

                    <div class="review-header">
                        <h3>
                            <?php echo htmlspecialchars($row['full_name']); ?>
                        </h3>

                        <span class="review-date">
                            <?php echo date("M d, Y", strtotime($row['created_at'])); ?>
                        </span>
                    </div>

                    <div class="stars">
                        <?php
                        for($i=1;$i<=5;$i++){
                            echo ($i <= $row['rating'])
                                ? '<i class="fas fa-star"></i>'
                                : '<i class="far fa-star"></i>';
                        }
                        ?>
                    </div>

                    <p class="feedback">
                        "<?php echo htmlspecialchars($row['feedback']); ?>"
                    </p>

                </div>

            <?php endwhile; ?>

        </div>

    <?php else: ?>

        <div class="empty">
            <i class="fas fa-comments"></i>
            <h2>No Reviews Yet</h2>
            <p>Customer reviews will appear here.</p>
        </div>

    <?php endif; ?>

</div>

</body>
</html>