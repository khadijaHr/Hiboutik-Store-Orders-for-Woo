<?php
/**
 * Plugin Name:         Hiboutik Store Orders for WooCommerce
 * Description:         Intègre les commandes magasin Hiboutik dans l'espace client WooCommerce. Affiche automatiquement les commandes passées en magasin physique dans la section « Mon compte », avec détails produits, statuts et points fidélité. Identification du client par numéro de téléphone.
 * Version:           	1.0.1
 * Requires at least: 	6.2
 * Requires PHP:      	7.0
 * Author:            	Khadija Har
 * Author URI:        	https://github.com/khadijahr
 * License:           	GPL v3 or later
 * License URI:       	https://www.gnu.org/licenses/gpl-3.0.html
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Définir la constante du chemin du plugin
define('HIBOUTIK_ORDERS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HIBOUTIK_ORDERS_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Enregistrer et charger les styles et scripts
function hiboutik_orders_enqueue_scripts() {
    // Charger uniquement sur la page "Mon compte" WooCommerce
    if (is_account_page()) {
        wp_enqueue_style(
            'hiboutik-orders-css',
            HIBOUTIK_ORDERS_PLUGIN_URL . 'assets/css/hiboutik-orders.css',
            array(),
            '1.0.1'
        );
        
        wp_enqueue_script(
            'hiboutik-orders-js',
            HIBOUTIK_ORDERS_PLUGIN_URL . 'assets/js/hiboutik-orders.js',
            array(),
            '1.0.1',
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'hiboutik_orders_enqueue_scripts');

// Fonction pour effectuer une requête API
function callAPI($url) {
    $ch = curl_init();
    	
	$username = get_option('hiboutik_user');
	$password = get_option('hiboutik_key');
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json'
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);

    //
    // $response = wp_remote_get($url);
    // if (is_wp_error($response)) {
    //     return [];
    // }
    // return json_decode(wp_remote_retrieve_body($response), true);
}

function hiboutik_display_store_orders() {
    // Configuration de l'API
    $baseUrl = "https://mystore.hiboutik.com/api/";

    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
    }
	 
    $email = $current_user->user_email;
	$phone = $current_user->phone_number;
	
	// echo $email.' '.$phone;
	
    $customers = callAPI($baseUrl . 'customers');
    $customer_id = null;

	$customer_phone = preg_replace('/\s+/', '', $phone);
    foreach ($customers as $customer) {
		
		 $hiboutik_phone = isset($customer['phone']) ? preg_replace('/[^0-9]/', '', $customer['phone']) : '';
		
		// Vérifier et supprimer l'indicatif (+212)
		if (substr($hiboutik_phone, 0, 3) === '212') {
			$hiboutik_phone = '0' . substr($hiboutik_phone, 3);
		}
		//
        if ($hiboutik_phone === $customer_phone) {
			$customer_id = $customer['customers_id'];
            break;
        }
		
        /*if ($customer['email'] === $email) {
            $customer_id = $customer['customers_id'];
            break;
        }*/
    }

    if (!$customer_id) {
        echo '<p>Aucun client trouvé.</p>';
        return;
    }

    $orders = callAPI($baseUrl . "customer/{$customer_id}/sales/");

    if (empty($orders)) {
        echo '<p>Aucune commande trouvée.</p>';
        return;
    }

    echo '<h2>Commandes du Magasin</h2>';
    echo '<article class="content"><table class="customer-orders-table"><tr><th>Commande</th><th>Date</th><th>État</th><th>Total</th><th>Points</th><th>Détails</th></tr>';

    foreach ($orders as $order) {
        $saleDetailUrl = "{$baseUrl}sales/{$order['sale_id']}";
        $order_details = callAPI($saleDetailUrl);

        if (!empty($order_details)) {
            $sale = $order_details[0];

			//
			$storeUrl = "{$baseUrl}stores/";
			$stores = callAPI($storeUrl);

             // Vérifier que sale_ext_ref est vide
            if (!empty($sale['sale_ext_ref'])) {
                continue; // si sale_ext_ref n'est PAS vide, on passe à la prochaine commande
            }

			$store_id = null;
			foreach ($stores as $store) {
				if ($store['store_id'] === $sale['store_id']) {
					$store_id = $store['store_name'];
					break;
				}
			}                        

            echo '<tr>';
			echo '<td>';
   				 echo 'N°' . esc_html($sale['sale_id']);
			echo '</td>';
            echo '<td>' . esc_html($sale['created_at']) . '<br/><b>Store:</b> '. $store_id. '</td>';
            echo '<td>' . (($order['completed_at'] == "0000-00-00 00:00:00") ? '<span style="color: green;">En cours</span>' : '<span style="color: blue;">Validée</span>') . '</td>';
            echo '<td>' . esc_html($sale['total']) . ' ' . esc_html($sale['currency']) . '</td>'; 
			echo '<td>' . esc_html($sale['points']) . '</td>'; 
            echo '<td style="text-align: center">';

            if (!empty($sale['line_items']) && is_array($sale['line_items'])) {
                echo '<button class="voir-plus-btn" data-saleid="' . esc_attr($order['sale_id']) . '">Voir</button>';
                echo '<div class="popup-overlay" id="popup-' . esc_attr($order['sale_id']) . '">';
                echo '<div class="popup-content">';
                echo '<span class="popup-close" data-saleid="' . esc_attr($order['sale_id']) . '">×</span>';
                echo '<h2>Détails de la commande</h2>';
                echo '<table class="details-table"><tr><th>Produit</th><th>Quantité</th><th>Prix</th><th>Total</th></tr>';
                foreach ($sale['line_items'] as $product) {
                    echo '<tr>';
                    echo '<td>' . esc_html($product['product_model']) . '<br/><b>' . (!empty($product['product_barcode']) ? esc_html($product['product_barcode']) : '') . '</b></td>';
                    echo '<td>' . esc_html($product['quantity']) . '</td>';
                    echo '<td>' . esc_html($product['product_price']) . ' ' . esc_html($product['product_currency']) . '</td>';
                    echo '<td>' . esc_html($product['item_total_gross']) . ' ' . esc_html($product['product_currency']) . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
                echo '</div>';
                echo '</div>';
            } else {
                echo '<p style="color: red;">Aucun détail trouvé.</p>';
            }

            echo '</td></tr>';
        }
    }
    echo '</table></article>';

    echo '<h2>Commandes sur site</h2>';
}

add_action('woocommerce_before_account_orders', 'hiboutik_display_store_orders');
