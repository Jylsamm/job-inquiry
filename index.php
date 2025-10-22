<?php
/**
 * WorkConnect PH - Landing Page (replaces home.php)
 */
$pageTitle = "Home";
require_once 'includes/header.php';
?>
<section class="hero">
	<h1>Find Your Next Opportunity</h1>
	<p>Connecting talent with the best companies in the Philippines. Your dream job is just a search away.</p>
</section>

<section class="features">
	<div class="container">
		<h2>Why Choose WorkConnect?</h2>
		<div class="feature-grid">
			<div class="feature-card">
				<i class="bi bi-briefcase-fill"></i>
				<h3>Tailored Job Matches</h3>
				<p>We match your skills and preferences to the perfect job openings.</p>
			</div>
			<div class="feature-card">
				<i class="bi bi-building-fill-check"></i>
				<h3>Verified Employers</h3>
				<p>Connect with trusted and verified companies looking for talent like you.</p>
			</div>
			<div class="feature-card">
				<i class="bi bi-person-fill-gear"></i>
				<h3>Career Resources</h3>
				<p>Access tools and resources to help you advance in your career.</p>
			</div>
		</div>
	</div>
</section>

<section class="cta">
	<div class="cta-box">
		<h2>Ready to Get Started?</h2>
		<p>Create an account today to explore job opportunities or find the perfect candidate for your company.</p>
		<a href="login.php" class="btn btn-primary">Join Now</a>
	</div>
</section>

<?php require_once 'includes/footer.php'; ?>
?>