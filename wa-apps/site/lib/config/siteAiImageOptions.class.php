<?php
class siteAiImageOptions
{
    public static function getSupportedAspectRatios()
    {
        return ["1:1", "16:9", "9:16", "4:3", "3:2", "2:3"];
    }

    public static function getParameterGroups()
    {
        return [
            "primary" => [
                [
                    'key' => 'scene',
                    'label' => _w('Scene'),
                    'options' => [
                        [
                            "label" => _w("Product on a white background"),
                            "prompt_text" => _w("Professional catalog photograph: PRODUCT placed exactly in the center of the frame against a pure white background (#FFFFFF). Show the entire product with a small margin on all sides. Use soft, even studio lighting, a natural shadow beneath the product, sharp details, and accurate colors and textures. Keep reflections controlled and avoid blown-out highlights. No props or unrelated objects."),
                        ],
                        [
                            "label" => _w("Product in a setting"),
                            "prompt_text" => _w("Create a realistic interior photograph. Product: PRODUCT. Location: LOCATION. Place the product as it would naturally appear in this setting. Add a few suitable props that highlight its purpose and convey its scale. Make the product the main focus, with no surrounding objects obscuring it. Use soft daylight, natural colors, and a slightly blurred background. No people."),
                        ],
                        [
                            "label" => _w("Person using a product"),
                            "prompt_text" => _w("Create a realistic photograph of a person using a product. Product: PRODUCT. Person: GENDER AND AGE. Location: LOCATION. Show one typical action that clearly demonstrates how to use the product. Make both the product and the person's interaction with it clearly visible. Use a natural pose, show the person engaged in the activity, and use soft lighting and a believable everyday setting."),
                        ],
                        [
                            "label" => _w("Model showcasing a product"),
                            "prompt_text" => _w("Create an advertising photograph of a person showcasing a product. Product: PRODUCT. Person: GENDER AND AGE. Have the person hold or present the product in a way that suits it. Choose framing that makes the product easy to see without hands obscuring it. Use a neutral studio background, soft portrait lighting, a natural facial expression, and realistic skin texture."),
                        ],
                        [
                            "label" => _w("Styled product flat lay"),
                            "prompt_text" => _w("Create a product photograph shot directly from above. Main product: PRODUCT. Surface: SURFACE. Additional items: PROPS. Arrange the items neatly in a harmonious composition, leaving small gaps between them. Make the main product stand out and remain clearly visible. Use soft side lighting, natural shadows, crisp textures, and a clean color palette."),
                        ],
                        [
                            "label" => _w("Packaging and contents"),
                            "prompt_text" => _w("Create a photograph showing a product and everything included with it. Product: PRODUCT. Packaging: PACKAGING DESCRIPTION. Included items: PACKAGE CONTENTS. Show the open packaging with the product and the listed items neatly arranged beside it. Make every item easy to distinguish. Use a light, neutral background, soft studio lighting, and sharp details. Do not add any items that are not listed as part of the package."),
                        ],
                        [
                            "label" => _w("Product details close-up"),
                            "prompt_text" => _w("Create a realistic close-up photograph of a product. Product: PRODUCT. Key detail: MATERIAL, TEXTURE, OR COMPONENT. Frame the shot around this detail so that its surface, shape, and finish are clearly visible. Use soft directional lighting to emphasize the texture, keep the background unobtrusive, and ensure the selected detail is in sharp focus. Preserve the material's actual properties and the product's construction."),
                        ],
                        [
                            "label" => _w("Product in hands close-up"),
                            "prompt_text" => _w("A close-up of elegant hands holding PRODUCT, with soft natural lighting, shallow depth of field, realistic skin texture, high-end commercial photography, and photorealism."),
                        ],
                        [
                            "label" => _w("Service, production, or team at work"),
                            "prompt_text" => _w("Create a realistic photograph of work in progress. Professional: PROFESSION / TEAM. Typical work activity: SERVICE / PRODUCTION / HANDCRAFTING. Location: WORKSPACE. Include tools, a client, or the result of the work in the frame if appropriate for the industry. Use natural lighting, candid expressions without stock-photo smiles, and a believable work environment that conveys professionalism and trust. Use a documentary photography style."),
                        ],
                        [
                            "label" => _w("Customer making a purchase"),
                            "prompt_text" => _w("A realistic scene: CLIENT / CUSTOMER (GENDER / AGE / STYLE) receiving a SERVICE or choosing a PRODUCT. Location: SETTING - STORE / SHOWROOM / APP. A PROFESSIONAL representing the business may also be present. Show a clear moment in the service experience, focusing on the customer, their participation, and their natural reaction. The people's actions and surrounding details should be appropriate for the service. Use soft lighting and a calm composition that conveys attentive care and professionalism."),
                        ],
                        [
                            "label" => _w("Expert or founder portrait"),
                            "prompt_text" => _w("Create a professional business portrait. Person: GENDER AND AGE. Profession: PROFESSION. Background: WORKSPACE. Show the person from the waist up, looking at the camera with a calm, friendly expression and a natural pose. Their clothing should suit their profession. Use soft portrait lighting, realistic skin texture, and a slightly blurred background. If a reference photo of the person is attached, preserve their recognizable features."),
                        ],
                        [
                            "label" => _w("Before and after"),
                            "prompt_text" => _w("Create a realistic image divided into two equal halves showing the states before and after using a PRODUCT or SERVICE. On the left, show the initial state: BEFORE. On the right, show the result: AFTER. Use a photorealistic style with matching lighting, perspective, and scale, and a clear division between the halves. Make the difference clearly visible and believable. No text or captions."),
                        ],
                        [
                            "label" => _w("Seasonal or promotional scene"),
                            "prompt_text" => _w("A festive advertising scene for CATEGORY / PRODUCT / SERVICE, with a theme and color palette inspired by SEASON / HOLIDAY / EVENT. Add a few recognizable thematic details and a suitable color scheme. Use inviting lighting, a tidy composition, and a photorealistic commercial photography style suitable for a website promotional banner."),
                        ],
                        [
                            "label" => _w("Icon"),
                            "prompt_text" => _w("An icon representing DELIVERY / PAYMENT / WARRANTY / SUPPORT in a 2D / 3D / HAND-DRAWN style. Use a consistent style, minimal detail, and soft shadows, isolated on a white background. Brand color(s): COLOR. Make the icon clear and easy to understand even at a small size. Center it with ample space around it."),
                        ],
                        [
                            "label" => _w("Infographic or chart"),
                            "prompt_text" => _w("An infographic for PRODUCT / SERVICE illustrating COMPOSITION, DIMENSIONS, INCLUDED ITEMS, BENEFITS. Use only the following facts and labels: DATA AND LABELS. Use a clean design with icons, labels, a grid layout, and brand colors. Do not add invented numbers, specifications, or claims."),
                        ],
                    ],
                ],
                [
                    'key' => 'niche',
                    'label' => _w('Niche'),
                    'options' => [
                        [
                            "heading" => _w("Food and beverages"),
                            "key" => "food_and_beverages",
                            "options" => [
                                [
                                    "label" => _w("Appetizing presentation"),
                                    "prompt_text" => _w("Professional advertising photography of food or a beverage: DISH, FOOD PRODUCT, OR BEVERAGE. Show it appetizingly presented in suitable tableware, highlighting its texture and characteristic ingredients. Surface and surroundings: SETTING. Soft side lighting, natural colors, the main subject in sharp focus, and a slightly blurred background. If a product photo is attached, preserve its appearance and ingredients. No added text."),
                                ],
                                [
                                    "label" => _w("In the kitchen"),
                                    "prompt_text" => _w("A realistic photograph of DISH OR FOOD PRODUCT being prepared in the kitchen of TYPE OF ESTABLISHMENT. Show a characteristic moment: handling ingredients, cooking, or final plating. Focus on the chef's hands, ingredients, and utensils. A clean work surface, natural action, soft lighting, and clearly visible food textures. Photorealistic. No added text."),
                                ],
                                [
                                    "label" => _w("The atmosphere of the venue"),
                                    "prompt_text" => _w("Create an interior photograph of CAFE, RESTAURANT, OR OTHER ESTABLISHMENT with the following style: INTERIOR STYLE AND FEATURES. Show a cozy seating area, table settings, and details that convey the character of the place. Natural light, realistic perspective, and a carefully arranged composition. If photos of the venue are attached, preserve its recognizable layout and decor. No added text."),
                                ],
                                [
                                    "label" => _w("A table for a gathering"),
                                    "prompt_text" => _w("Create a realistic restaurant photograph of a table set for OCCASION OR TYPE OF GATHERING. The table features DISHES AND BEVERAGES. Show a variety of presentations and the pleasant atmosphere of a shared meal. A slightly elevated camera angle, soft light, natural colors, and a neat table setting. Guests' hands may appear at the edges of the frame. Preserve the appearance of the dishes shown in the reference images. No added text."),
                                ],
                                [
                                    "label" => _w("Takeout order"),
                                    "prompt_text" => _w("Create an advertising photograph of a takeout food order. Order contents: DISHES AND BEVERAGES. Packaging: PACKAGING DESCRIPTION. Show open containers with their contents neatly arranged so that portion sizes and everything included in the order are clear. A clean surface, soft daylight, and appetizing textures. If reference images are provided, preserve the packaging and labeling. Do not add unspecified dishes, utensils, or new text."),
                                ],
                                [
                                    "label" => _w("Seasonal menu banner"),
                                    "prompt_text" => _w("Create an advertising banner for a seasonal menu. Dishes and beverages: LIST. Season or occasion: SEASON OR EVENT. Arrange an appetizing composition on the RIGHT side of the frame and add a few appropriate seasonal details. Leave an uncluttered area on the LEFT for a headline, price, and order button. Soft side lighting, distinctive textures, and natural colors. Preserve the appearance of the dishes shown in the reference images. Do not render the text or prices themselves."),
                                ],
                            ]
                        ],
                        [
                            "heading" => _w("Beauty and personal care"),
                            "key" => "beauty_and_personal_care",
                            "options" => [
                                [
                                    "label" => _w("Salon treatment"),
                                    "prompt_text" => _w("A realistic advertising photograph of TREATMENT at a beauty salon. Client: GENDER AND AGE. Show a characteristic moment of the beauty professional at work, with tools appropriate for the treatment and a tidy workstation. Emphasize attention to the client and precision of movement. Soft light, natural skin texture, and a calm, professional atmosphere. No added text."),
                                ],
                                [
                                    "label" => _w("The finished result"),
                                    "prompt_text" => _w("Professional beauty photography of the result of HAIRCUT, HAIR COLORING, MAKEUP, MANICURE, OR OTHER SERVICE. Features of the result: DESCRIPTION. Choose an angle and framing that clearly show the work. A neutral background, soft lighting, accurate shades and details, and moderate retouching that preserves natural texture. If a reference image is provided, use it as a guide. No added text."),
                                ],
                                [
                                    "label" => _w("Cosmetics and textures"),
                                    "prompt_text" => _w("Create an advertising product photograph of a cosmetic product: PRODUCT. Show the packaging next to a small sample of its texture: TEXTURE DESCRIPTION. A clean composition, soft studio lighting, controlled highlights, and clearly visible surfaces. If a product photo is attached, preserve its shape, color, packaging, and labeling. Do not add ingredients that are not specified in the request or any new text."),
                                ],
                                [
                                    "label" => _w("Consultation before a treatment"),
                                    "prompt_text" => _w("Create a realistic photograph of a consultation before TREATMENT. A beauty professional discusses PREFERENCES OR POSSIBLE RESULTS with the client, using appropriate samples or materials. Show natural interaction and attention to the client's preferences. A calm salon interior, soft lighting, and realistic skin tones. No staged gestures, invented labels on samples, or added advertising text."),
                                ],
                                [
                                    "label" => _w("At-home care"),
                                    "prompt_text" => _w("Create a realistic advertising photograph of PRODUCT being used at home. Person: GENDER AND AGE. Show a characteristic moment in a care routine in a calm home setting, with both the product and the action clearly visible. Soft daylight, natural skin texture, and minimal props. Preserve the appearance of the packaging shown in the reference image. No added text."),
                                ],
                                [
                                    "label" => _w("Salon booking banner"),
                                    "prompt_text" => _w("Create a photographic banner for booking SERVICE. Salon decor: STYLE AND COLORS. On the RIGHT side of the frame, show a neatly prepared workstation and characteristic tools or materials that make the service recognizable. Leave an uncluttered area on the LEFT for an offer and a booking button. Soft light, clean textures, and a sense of attention to detail. Do not render buttons, prices, or text."),
                                ],
                            ]
                        ],
                        [
                            "heading" => _w("Healthcare and wellness"),
                            "key" => "healthcare_and_wellness",
                            "options" => [
                                [
                                    "label" => _w("Specialist consultation"),
                                    "prompt_text" => _w("A realistic photograph of a consultation with a specialist in SPECIALTY. Patient: GENDER AND AGE. Show a calm conversation in a bright consulting room: the specialist listens attentively or explains clearly, and the patient takes part in the conversation. Natural poses, professional attire appropriate to the role, a tidy setting, and soft daylight. An atmosphere of care and trust, with no staged gestures or added text."),
                                ],
                                [
                                    "label" => _w("Diagnostic procedure"),
                                    "prompt_text" => _w("Create a realistic photograph of a diagnostic procedure: PROCEDURE OR EXAMINATION. Show the specialist, patient, and equipment in a typical working situation. Emphasize calm interaction and a neatly organized space. A clean consulting room, soft neutral lighting, and natural colors. If an equipment photo is provided, preserve its appearance. No invented readings on screens or added text."),
                                ],
                                [
                                    "label" => _w("Inside the clinic"),
                                    "prompt_text" => _w("Professional interior photography of CLINIC AREA: RECEPTION, CONSULTING ROOM, OR WAITING AREA. Decor: STYLE AND COLORS. Show a comfortable, clean, and welcoming space with a clear layout. Bright natural lighting, straight perspective, and realistic materials. If photos of the space are provided, preserve its layout and distinctive details. No added text."),
                                ],
                                [
                                    "label" => _w("Laboratory work"),
                                    "prompt_text" => _w("Create a realistic photograph of laboratory work in RESEARCH FIELD. Show a specialist preparing or examining samples using EQUIPMENT. An organized workstation, protective clothing appropriate to the process, neutral lighting, and clear details. If a reference image of the equipment is provided, preserve its appearance. No personal data, invented research results, or added text."),
                                ],
                                [
                                    "label" => _w("Medical specialty"),
                                    "prompt_text" => _w("Create a single illustration for a card representing a medical specialty or service: SPECIALTY OR SERVICE. Convey the subject through a simple, recognizable image: a characteristic instrument, diagnostic equipment, or a simplified depiction of an organ. Clean flat graphics, soft shapes, a palette of COLORS, and a light background. The image should remain clear at a small size. If an example from the series is provided, preserve its style. No text, diagnoses, or promises about treatment outcomes."),
                                ],
                            ]
                        ],
                        [
                            "heading" => _w("Clothing and accessories"),
                            "key" => "clothing_and_accessories",
                            "options" => [
                                [
                                    "label" => _w("An outfit on a model"),
                                    "prompt_text" => _w("Create a realistic fashion photograph. Clothing or accessory: PRODUCT. Model: GENDER AND AGE. Shoot location: LOCATION. Show the item's natural fit and a complete outfit, choosing a pose that clearly reveals the silhouette and key details. Soft light, accurate colors, and textures. If a product photo is provided, preserve its cut, pattern, and labeling. No added text."),
                                ],
                                [
                                    "label" => _w("Details and materials"),
                                    "prompt_text" => _w("Professional close-up photography of clothing or an accessory. Product: PRODUCT. Highlight a particularly important detail: FABRIC, SEAM, HARDWARE, OR OTHER ELEMENT. Side lighting emphasizes the texture, the chosen detail is in sharp focus, and the surroundings are not distracting. If a product photo is provided, preserve its construction, material, and color. No added text."),
                                ],
                                [
                                    "label" => _w("A complete outfit"),
                                    "prompt_text" => _w("Create a photograph of a fashion outfit shot directly from above. Main item: PRODUCT. Complementary clothing and accessories: OUTFIT CONTENTS. Arrange them neatly on an uncluttered surface, leaving space between them and maintaining clear proportions. Soft daylight, natural shadows, and a harmonious composition. Preserve the appearance of the items shown in the attached reference images. No added text."),
                                ],
                                [
                                    "label" => _w("New collection"),
                                    "prompt_text" => _w("Create an advertising banner for a collection: COLLECTION DESCRIPTION. Season and audience: SEASON AND AUDIENCE. Show one complete outfit on a model in an appropriate setting that conveys the character of the collection. Position the model on the RIGHT and leave an uncluttered background on the LEFT for a headline and button. Expressive fashion photography, a natural fit of the clothing, and soft light. Preserve the clothing shown in the reference images. Do not add text, prices, or logos."),
                                ],
                                [
                                    "label" => _w("Footwear in motion"),
                                    "prompt_text" => _w("Create an advertising photograph of footwear: FOOTWEAR DESCRIPTION, worn while walking on SURFACE OR LOCATION. Use a low camera angle, showing the legs and some of the surrounding space. The footwear is clearly visible, the stride looks natural, and the lighting emphasizes the material and shape of the sole. Preserve the appearance of the footwear model shown in the reference image. No added text."),
                                ],
                                [
                                    "label" => _w("Jewelry being worn"),
                                    "prompt_text" => _w("Create a realistic advertising photograph of a person wearing JEWELRY. Show the relevant part of the body in close-up so that the size, fit, and details of the piece are clear. Soft directional light, natural skin texture, and controlled highlights on the metal and stones. Preserve the construction and materials of the jewelry shown in the reference image. No distracting accessories or added text."),
                                ],
                            ]
                        ],
                        [
                            "heading" => _w("Home and interiors"),
                            "key" => "home_and_interiors",
                            "options" => [
                                [
                                    "label" => _w("Furniture in an interior"),
                                    "prompt_text" => _w("Create a realistic interior photograph featuring a piece of furniture: ITEM. Room and style: INTERIOR DESCRIPTION. Place the furniture naturally, showing its scale and how it works with its surroundings. Daylight, realistic materials, tasteful decor, and a clear view of the main item. If a furniture photo is provided, preserve its construction, proportions, upholstery, and color. No added text."),
                                ],
                                [
                                    "label" => _w("Cozy details"),
                                    "prompt_text" => _w("Create an atmospheric photograph of home goods: ITEMS. Show a small, lived-in corner in ROOM OR AREA OF THE HOME where these items are naturally used. A thoughtful combination of textures, a few everyday details, soft light, and a calm composition. The items are clearly visible. Preserve the appearance of the products shown in the reference images. No added text."),
                                ],
                                [
                                    "label" => _w("Materials up close"),
                                    "prompt_text" => _w("Create a product photograph showing the material and craftsmanship of FURNITURE OR INTERIOR ITEM. Main detail: TEXTURE, JOINT, UPHOLSTERY, OR FINISH. Show it in close-up while keeping the item recognizable. Soft directional light emphasizes the relief and actual properties of the surface. An uncluttered background and accurate colors. No added text."),
                                ],
                                [
                                    "label" => _w("Tableware in a table setting"),
                                    "prompt_text" => _w("Create an interior product photograph of a table setting featuring TABLEWARE AND DINING ACCESSORIES. Style and palette: DESCRIPTION. Show how the items work together on the table. A few appropriate textiles and decorative details, soft daylight, and clearly defined shapes and materials. Keep the products as the main focus. Preserve their appearance as shown in the reference images. No added text."),
                                ],
                                [
                                    "label" => _w("Textiles in a room"),
                                    "prompt_text" => _w("Create a realistic interior photograph of a textile product: PRODUCT, in ROOM OR AREA OF THE HOME. Show natural folds, the texture of the fabric, and how it complements the furniture. Soft light emphasizes the material and volume, while the composition conveys a sense of comfort. If a product photo is provided, preserve its color, pattern, trim, and proportions. No added text."),
                                ],
                                [
                                    "label" => _w("Lighting in an interior"),
                                    "prompt_text" => _w("Create a realistic interior photograph featuring a light fixture: LIGHT FIXTURE. Room: INTERIOR DESCRIPTION. Show the fixture switched on and how its light falls across nearby surfaces. Subdued evening lighting, clearly visible details of the fixture's body, and natural shadows and reflections. Preserve the product's construction and material shown in the reference image. No excessive glow or added text."),
                                ],
                            ]
                        ],
                        [
                            "heading" => _w("Construction and renovation"),
                            "key" => "construction_and_renovation",
                            "options" => [
                                [
                                    "label" => _w("Completed renovation"),
                                    "prompt_text" => _w("Professional interior photograph of a completed renovation of SPACE. Style and finish details: DESCRIPTION. Show the entire space, highlighting the combination of materials, neat joints, and well-planned lighting. Natural perspective, straight vertical lines, and realistic colors. If photographs or a design are attached, preserve the specified layout. No construction debris. No added text."),
                                ],
                                [
                                    "label" => _w("Tradesperson at work"),
                                    "prompt_text" => _w("Realistic photograph of TYPE OF WORK in progress. Show a tradesperson performing a specific action with the appropriate tools and materials. Workwear and protective equipment must suit the task. Focus on the hands, tool, and surface being worked on. Natural lighting, an organized work area, and a documentary photography style. No added text."),
                                ],
                                [
                                    "label" => _w("Materials and finishes"),
                                    "prompt_text" => _w("Create a product photograph of a combination of finishing materials: MATERIALS AND COLORS. Arrange the samples on a work surface so that their textures, thicknesses, and color compatibility are clearly visible. Soft side lighting, crisp details, and a calm composition in the style of an interior design presentation. Preserve the appearance of the samples from the reference images. No added text."),
                                ],
                                [
                                    "label" => _w("Measuring and planning"),
                                    "prompt_text" => _w("Create a realistic photograph of preparations for TYPE OF WORK. Show a specialist taking measurements or checking dimensions against a plan in TYPE OF SPACE. The task being performed and the appropriate tools must be clearly visible. Natural light, a realistic project setting, and a tidy composition. No invented legible measurements or random drawings. No added text."),
                                ],
                                [
                                    "label" => _w("House construction"),
                                    "prompt_text" => _w("Create a realistic photograph of the construction of TYPE OF HOUSE at CONSTRUCTION STAGE. Show the characteristic structural elements, materials, and a small crew at work. Realistic scale, an organized site, and workwear and protective equipment appropriate to the task. Soft daylight. If a design or photographs are attached, preserve the architectural features. No added text."),
                                ],
                                [
                                    "label" => _w("Renovation stages"),
                                    "prompt_text" => _w("Create a single clear illustration of the renovation stages for TYPE OF SPACE. Sequence: LIST OF 3–4 STAGES. Show the same simplified room at each stage, preserving the layout and viewpoint. Simple isometric graphics, a light background, and neat arrows between stages. Leave room for captions. Do not add any work that has not been specified; do not depict the captions themselves, timelines, or prices."),
                                ],
                            ]
                        ],
                        [
                            "heading" => _w("Real estate"),
                            "key" => "real_estate",
                            "options" => [
                                [
                                    "label" => _w("Property exterior"),
                                    "prompt_text" => _w("Create an architectural photograph of PROPERTY TYPE AND DESCRIPTION. Show the building from a flattering angle, maintaining a natural perspective and straight vertical lines. Soft daylight, a clearly visible facade, and tidy surroundings. If photographs or renderings of the property are attached, preserve the architecture, number of stories, and entrance locations. Do not add unspecified amenities or new text."),
                                ],
                                [
                                    "label" => _w("Interior space"),
                                    "prompt_text" => _w("Professional interior photograph of TYPE OF SPACE. Features: LAYOUT, FINISHES, AND IMPORTANT DETAILS. Show the space from an angle that helps convey its layout and dimensions. Natural light, realistic proportions, and a moderate wide-angle view without stretched walls. If reference images are provided, preserve the layout, windows, and doorways. No added text."),
                                ],
                                [
                                    "label" => _w("Grounds and surroundings"),
                                    "prompt_text" => _w("Create a realistic architectural photograph of the grounds of PROPERTY. Show the following elements of the surroundings: LIST OF ELEMENTS. A well-considered composition, natural distances and scale, and soft daylight. If a plan or photographs are attached, follow the positioning of the features shown. Do not add pools, parks, recreational areas, or other amenities absent from the description. No added text."),
                                ],
                                [
                                    "label" => _w("Property viewing with an agent"),
                                    "prompt_text" => _w("Create a realistic photograph of a viewing of PROPERTY TYPE. Clients: DESCRIPTION OF BUYERS OR TENANTS. Show a real estate agent drawing the clients' attention to a specific feature of the space. Natural interaction, soft daylight, and a clear view of the interior. If photographs of the property are attached, preserve its layout and finishes. No staged key handover. No added text."),
                                ],
                                [
                                    "label" => _w("Commercial space"),
                                    "prompt_text" => _w("Create an architectural photograph of a space for BUSINESS USE. Condition and features: DESCRIPTION OF THE SPACE. Show the space so that its layout, entrance area, and possibilities for positioning equipment or workstations are clear. Straight vertical lines, natural lighting, and realistic proportions. Preserve the layout from the reference images. No added text."),
                                ],
                                [
                                    "label" => _w("Residential development banner"),
                                    "prompt_text" => _w("Create an architectural image for the hero section of a residential development website: PROPERTY DESCRIPTION. Use the attached photographs or renderings as the basis, preserving the architecture, number of stories, and actual surroundings. Choose a striking view in which the buildings occupy the RIGHT side of the frame, leaving an uncluttered area on the LEFT for a headline and button. Soft daylight and natural materials. Do not add unspecified amenities, prices, or text."),
                                ],
                            ]
                        ],
                        [
                            "heading" => _w("Cars and auto services"),
                            "key" => "cars_and_auto_services",
                            "options" => [
                                [
                                    "label" => _w("Vehicle diagnostics"),
                                    "prompt_text" => _w("Realistic advertising photograph of a TYPE OF DIAGNOSTIC OR REPAIR SERVICE. Vehicle: MAKE AND MODEL. Show a mechanic performing a typical task in a tidy service bay. Appropriate tools and workwear, with a realistically organized space. Focus on the process and attention to detail, with soft neutral lighting. No fabricated readings on screens. No added text."),
                                ],
                                [
                                    "label" => _w("Car exterior care"),
                                    "prompt_text" => _w("Create a realistic photograph of a CAR WASH, POLISHING, OR OTHER CARE SERVICE. Vehicle: MAKE, MODEL, AND COLOR. Show a characteristic moment of the work in a close-up or medium shot. Emphasize the surface texture, careful movements, and controlled light reflections. Do not obscure the vehicle with excessive foam or glare. No added text."),
                                ],
                                [
                                    "label" => _w("Car advertising"),
                                    "prompt_text" => _w("Create an advertising photograph of a MAKE, MODEL, AND COLOR vehicle at SHOOT LOCATION. Three-quarter view, with the entire vehicle in the frame, a striking silhouette, and natural proportions. Soft directional light highlights the shape of the body, while the surroundings do not distract. If photographs of the vehicle are attached, preserve its specification and exterior details. No added text."),
                                ],
                                [
                                    "label" => _w("Car interior"),
                                    "prompt_text" => _w("Create a realistic advertising photograph of the interior of a MAKE AND MODEL vehicle. Focus on SEATS, DASHBOARD, OR OTHER DETAILS. Choose an angle that shows the layout and quality of the materials. Soft light, controlled reflections, and crisp textures. If photographs are attached, preserve the specification, trim, and placement of controls. No added text."),
                                ],
                                [
                                    "label" => _w("Wheels and tires"),
                                    "prompt_text" => _w("Create an advertising product photograph of a TIRE, RIM, OR COMPLETE WHEEL ASSEMBLY. Show the product at a slight angle so that the tread pattern, profile, or rim design is clearly visible. An uncluttered auto service setting or a neutral surface, soft directional light, and crisp texture. Preserve the product details and markings from the reference image. No new text."),
                                ],
                                [
                                    "label" => _w("Auto service booking"),
                                    "prompt_text" => _w("Create a photographic banner for booking AUTO SERVICE. On the RIGHT side of the frame, show the relevant area of the vehicle being worked on and an appropriate tool or a mechanic performing the task. Leave an uncluttered background on the LEFT for the offer, price, and booking button. A tidy service bay, soft directional light, and clearly defined metal and rubber surfaces. Preserve the vehicle from the reference image. Do not depict text, a price, or a button."),
                                ],
                            ]
                        ],
                        [
                            "heading" => _w("Education and courses"),
                            "key" => "education_and_courses",
                            "options" => [
                                [
                                    "label" => _w("Group lesson"),
                                    "prompt_text" => _w("Realistic photograph of a lesson on SUBJECT OR SKILL. Participants: AGE AND AUDIENCE TYPE. Show a teacher and a small group discussing a topic or completing an assignment. The setting and learning materials must suit the subject. Natural poses, engaged participants, and soft daylight. No staged gestures or illegible text on the board. No added text."),
                                ],
                                [
                                    "label" => _w("Online learning"),
                                    "prompt_text" => _w("Create a realistic photograph of a person studying COURSE TOPIC on a laptop. Learner: AGE AND AUDIENCE DESCRIPTION. Show focused work, notes, and a few appropriate learning supplies. A comfortable workspace, soft natural light, and a calm composition. The screen is viewed at an angle, without small fabricated text. No added text."),
                                ],
                                [
                                    "label" => _w("Practical lesson"),
                                    "prompt_text" => _w("Create a realistic photograph of hands-on instruction in SKILL. Participants: AGE AND AUDIENCE TYPE. Show a learner performing a specific action while a teacher assists or demonstrates a technique. Hands, tools, and work in progress must be clearly visible. A natural working atmosphere and soft light. No added text."),
                                ],
                                [
                                    "label" => _w("Children's activity"),
                                    "prompt_text" => _w("Create a realistic photograph of a children's lesson on TOPIC. Children's age: AGE. Show a small group engaged in a clear hands-on activity with a teacher involved. Learning materials must suit the age group and subject, with varied faces and poses. A bright room, soft daylight, and an atmosphere of curiosity and engagement. No added text."),
                                ],
                                [
                                    "label" => _w("Practical assignment result"),
                                    "prompt_text" => _w("Create a realistic photograph of the result of a learning assignment in DISCIPLINE OR SKILL. Show DESCRIPTION OF THE WORK at a workstation alongside the tools and materials used. The main focus is on the object created and its details. Soft light and a tidy composition. If photographs of the work are attached, preserve its characteristics. No invented awards. No added text."),
                                ],
                                [
                                    "label" => _w("Course cover"),
                                    "prompt_text" => _w("Create a course cover on COURSE TOPIC for an audience of AUDIENCE DESCRIPTION. Show a clear composition of characteristic tools, learning materials, or objects related to the skill being learned. A simple three-dimensional illustration, soft shadows, and a coordinated palette. The main visual must be easy to recognize at card size; leave an uncluttered area at the top for the title. Do not add diplomas, promises of results, or text."),
                                ],
                            ]
                        ],
                        [
                            "heading" => _w("Sports and fitness"),
                            "key" => "sports_and_fitness",
                            "options" => [
                                [
                                    "label" => _w("Personal training"),
                                    "prompt_text" => _w("Realistic sports photograph of a one-on-one TYPE OF TRAINING session. Client: GENDER, AGE, AND FITNESS LEVEL. Show a trainer explaining a movement or helping the client perform an exercise. Natural poses and proportions, appropriate equipment, and lighting that emphasizes the action. An atmosphere of attentiveness and concentration. No added text."),
                                ],
                                [
                                    "label" => _w("Group class"),
                                    "prompt_text" => _w("Create a realistic photograph of a group ACTIVITY TYPE class. Participants: AUDIENCE DESCRIPTION. Show a small group in motion, with varied natural poses and facial expressions. People must not completely obscure one another, and the instructor must be clearly identifiable. A spacious, appropriate room, lively lighting, and a sense of energy and teamwork. No added text."),
                                ],
                                [
                                    "label" => _w("Gym and equipment"),
                                    "prompt_text" => _w("Create an interior photograph of TYPE OF SPORTS FACILITY. Main equipment: LIST. Show a convenient arrangement of zones, sufficient space between pieces of equipment, and a well-kept interior. Natural perspective, soft lighting, and crisp details. If photographs of the gym are attached, preserve its layout and equipment. No people. No added text."),
                                ],
                                [
                                    "label" => _w("Outdoor workout"),
                                    "prompt_text" => _w("Create a realistic sports photograph of TYPE OF ACTIVITY at LOCATION. Participants: AUDIENCE DESCRIPTION. Show a lively moment of movement with natural poses and facial expressions. Clothing and equipment must suit the activity and weather. Striking daylight, a clearly distinguishable main participant, and surroundings that convey the workout location. No added text."),
                                ],
                                [
                                    "label" => _w("Membership banner"),
                                    "prompt_text" => _w("Create an advertising photograph for a membership offer for ACTIVITY TYPE OR CLASS FORMAT. Audience: AUDIENCE DESCRIPTION. Show one person performing a characteristic movement in an appropriate sports setting. Position the person on the RIGHT, leaving an uncluttered area on the LEFT for the terms and a button. Striking lighting, a natural physique, and a clearly readable pose. Do not depict a price, a membership card, promises of body transformation, or text."),
                                ],
                            ]
                        ],
                        [
                            "heading" => _w("Travel and hotels"),
                            "key" => "travel_and_hotels",
                            "options" => [
                                [
                                    "label" => _w("A room for relaxation"),
                                    "prompt_text" => _w("A professional interior photograph of a room in TYPE OF HOTEL OR ACCOMMODATION. Style and features: DESCRIPTION. Show the sleeping area, a seating area, and distinctive details that convey a sense of comfort. Soft daylight, natural materials, and a carefully arranged composition. If reference images are provided, preserve the layout, furniture, and actual view from the window. No added text."),
                                ],
                                [
                                    "label" => _w("Travel experiences"),
                                    "prompt_text" => _w("Create a realistic travel photograph of a trip to LOCATION. Main experience: WALK, EXCURSION, OR OTHER ACTIVITY. Show a characteristic moment involving TYPE OF TRAVELERS. Natural emotions, recognizable features of the area, soft lighting, and a sense of being there. Do not overcrowd the image with landmarks. No added text."),
                                ],
                                [
                                    "label" => _w("A place worth visiting"),
                                    "prompt_text" => _w("Create an evocative landscape photograph of LOCATION OR RECREATION AREA in SEASON. Show the main view and a few distinctive foreground details that convey depth. Natural colors and soft morning or evening light. Leave an uncluttered area of sky or landscape for a headline. If reference images are provided, preserve the actual features of the location. Do not render the text itself."),
                                ],
                                [
                                    "label" => _w("Breakfast at the hotel"),
                                    "prompt_text" => _w("Create a realistic photograph of breakfast in TYPE OF HOTEL OR ACCOMMODATION. On the table: DISHES AND BEVERAGES. Show an inviting table setting in surroundings characteristic of the location, with soft morning light and a peaceful atmosphere. The food looks appetizing, and the interior complements the scene. If reference images are provided, preserve the features of the room and the view from the window. No added text."),
                                ],
                                [
                                    "label" => _w("An active travel route"),
                                    "prompt_text" => _w("Create a realistic travel photograph of TYPE OF ACTIVITY along a route in LOCATION. Show a small group of travelers at a characteristic moment in their journey. Their equipment suits the activity, and the weather and landscape are consistent with the area. Strong depth, natural light, and a sense of movement and discovery. No invented signs or added text."),
                                ],
                                [
                                    "label" => _w("Hotel special offer"),
                                    "prompt_text" => _w("Create an advertising banner for the offer OFFER DESCRIPTION at HOTEL OR VACATION DESTINATION. Show one scene that conveys the main experience of this offer: the accommodation, breakfast, relaxation, or activity specified in the description. Soft natural light and a calm, inviting atmosphere. Leave space on one side for the offer terms and a booking button. Preserve the actual features of the location shown in the reference images. Do not add unspecified amenities, prices, or text."),
                                ],
                            ]
                        ],
                        [
                            "heading" => _w("Celebrations and events"),
                            "key" => "celebrations_and_events",
                            "options" => [
                                [
                                    "label" => _w("Venue styling"),
                                    "prompt_text" => _w("Create a realistic photograph of a venue for TYPE OF EVENT. Design concept: THEME AND COLORS. Show an overall view of the decor, table settings, and main areas, maintaining a cohesive composition. Realistic materials, soft festive lighting, and carefully arranged details. If reference images are attached, use them as the basis for the styling. No people or added text."),
                                ],
                                [
                                    "label" => _w("An event in full swing"),
                                    "prompt_text" => _w("Create a documentary-style photograph of TYPE OF EVENT. Participants: GUEST DESCRIPTION. Show a characteristic, lively moment: conversation, a performance, a shared activity, or guests reacting. Natural emotions, varied poses, and expressive venue lighting. The main moment is easy to read, and the background supports the atmosphere. No staged group smiles at the camera or added text."),
                                ],
                                [
                                    "label" => _w("Preparing for an event"),
                                    "prompt_text" => _w("A realistic photograph of preparations for TYPE OF EVENT. Show a team working on DECOR, TABLE SETTINGS, A STAGE, OR ANOTHER TASK. Focus on hands, materials, and attention to detail. An organized process, coordinated styling, and soft natural light. The image conveys anticipation of the event. No added text."),
                                ],
                                [
                                    "label" => _w("Wedding ceremony"),
                                    "prompt_text" => _w("Create a realistic documentary-style photograph of a wedding ceremony in LOCATION. Styling: STYLE AND COLORS. Show the couple and a characteristic emotional moment from the ceremony, keeping gestures and facial expressions natural. Soft light and coordinated decor; guests do not obscure the couple. If reference images of the styling are attached, follow them. No added text."),
                                ],
                                [
                                    "label" => _w("Business conference"),
                                    "prompt_text" => _w("Create a realistic photograph of a conference on TOPIC. Show the speaker and part of the audience in TYPE OF VENUE. Focus on lively interaction: a question from the audience, an explanation, or attentive listening. Realistic stage lighting and a well-organized space. If there is a screen, use understated graphics without invented data. No added text."),
                                ],
                            ]
                        ],
                        [
                            "heading" => _w("Flowers and gifts"),
                            "key" => "flowers_and_gifts",
                            "options" => [
                                [
                                    "label" => _w("Bouquet close-up"),
                                    "prompt_text" => _w("Create an advertising photograph of a bouquet. Flowers and palette: FLOWERS AND COLORS. Wrapping: DESCRIPTION. Show the bouquet's distinctive shape, the natural texture of its petals, and the freshness of the flowers. Soft side lighting and an uncluttered background, with the entire bouquet in the frame. If a photo is attached, preserve the bouquet's flowers, proportions, and styling. No added text."),
                                ],
                                [
                                    "label" => _w("Gift set"),
                                    "prompt_text" => _w("Create a product photograph of a gift set for OCCASION. Contents: SET CONTENTS. Show open packaging and neatly arranged items so that each one is clearly distinguishable. Soft studio lighting, a coordinated palette, and expressive textures of the paper, ribbons, and contents. Preserve the products shown in the reference images; do not add extra gifts or new text."),
                                ],
                                [
                                    "label" => _w("Arranging a bouquet"),
                                    "prompt_text" => _w("A realistic photograph of a florist working on a bouquet of FLOWERS. Show the hands while selecting stems, arranging the bouquet, or wrapping it. Only the necessary materials and tools are on the worktable. Soft daylight, natural colors, and clearly visible plant textures. An atmosphere of careful craftsmanship. No added text."),
                                ],
                                [
                                    "label" => _w("The moment of giving"),
                                    "prompt_text" => _w("Create a realistic advertising photograph of someone giving BOUQUET OR GIFT for OCCASION. Show a natural moment of handing it over and a subtle, spontaneous reaction from the recipient. The gift is clearly visible, with no hands obscuring it. Soft light, an appropriate setting, and an uncluttered background. Preserve the appearance of the gift shown in the reference image. No added text."),
                                ],
                                [
                                    "label" => _w("Corporate gifts"),
                                    "prompt_text" => _w("Create an advertising photograph of a corporate gift set. Contents: ITEMS. Color scheme: COLORS. Show several neatly assembled sets and one open set so that the contents and packaging are visible. Soft studio lighting, coordinated styling, and a clean composition. Use only the logo from the attached reference image; if none is provided, do not add logos or text."),
                                ],
                                [
                                    "label" => _w("Holiday banner"),
                                    "prompt_text" => _w("Create an advertising banner for flowers or gifts for HOLIDAY OR OCCASION. Main product: BOUQUET OR GIFT. Position it on the RIGHT, and add a little themed decor that matches the colors and mood. Leave an uncluttered background on the LEFT for a greeting, an offer, and an order button. Soft studio lighting, crisp textures, and a carefully arranged composition. Preserve the product shown in the reference image. Do not render the text, price, or button itself."),
                                ],
                            ]
                        ],
                        [
                            "heading" => _w("Pet supplies and care"),
                            "key" => "pet_supplies_and_care",
                            "options" => [
                                [
                                    "label" => _w("A pet with a product"),
                                    "prompt_text" => _w("Create a realistic advertising photograph of PRODUCT in use. Pet: SPECIES, BREED, AND SIZE. Show the animal interacting naturally with the item in an appropriate indoor or outdoor setting. The product is clearly visible, and the scale is realistic. Soft light and lifelike fur texture. If a product photo is attached, preserve its appearance. No added text."),
                                ],
                                [
                                    "label" => _w("Pet care"),
                                    "prompt_text" => _w("Create a realistic photograph of PET CARE SERVICE. Animal: SPECIES AND BREED. Show a professional working calmly and carefully, with appropriate tools and a comfortable workspace. The animal is in a natural position; the professional is attentive. Soft light and clean surroundings. No added text."),
                                ],
                                [
                                    "label" => _w("A comfortable spot at home"),
                                    "prompt_text" => _w("Create a realistic photograph of a home space for PET SPECIES AND SIZE. Main product: PET BED, PET HOUSE, SCRATCHING POST, OR OTHER ITEM. Show the animal next to or inside the product so that its purpose and size are clear. A cozy interior, soft daylight, and minimal unnecessary details. Preserve the appearance of the product shown in the reference image. No added text."),
                                ],
                                [
                                    "label" => _w("A walk with a pet"),
                                    "prompt_text" => _w("Create a realistic photograph of a walk with a pet: SPECIES, BREED, AND SIZE. Main product: LEASH, HARNESS, CARRIER, OR OTHER ACCESSORY. Show a natural moment of use outdoors or in a park. The product is clearly distinguishable, with a realistic size and fit. Soft daylight and lively movement. Preserve the appearance of the product shown in the reference image. No added text."),
                                ],
                                [
                                    "label" => _w("Pet food and treats"),
                                    "prompt_text" => _w("Create an advertising photograph of PET FOOD OR TREAT for ANIMAL SPECIES. Show the packaging next to a small amount of the product itself in an appropriate bowl or on a neutral surface. Soft studio lighting, crisp textures, and a clean composition. Preserve the packaging and the appearance of the product shown in the reference images. Do not add unspecified ingredients or new text."),
                                ],
                                [
                                    "label" => _w("Pet store banner"),
                                    "prompt_text" => _w("Create an advertising banner for the product category CATEGORY for ANIMAL SPECIES. Show a pet next to several representative products from this category. Position the composition on the RIGHT, and leave uncluttered space on the LEFT for a headline and a button. Soft light, a friendly atmosphere, and realistically scaled objects. Preserve the products shown in the reference images. Do not add products from other categories, prices, or text."),
                                ],
                            ]
                        ],
                        [
                            "heading" => _w("Business services"),
                            "key" => "business_services",
                            "options" => [
                                [
                                    "label" => _w("Client consultation"),
                                    "prompt_text" => _w("Create a realistic business photograph of a consultation about SERVICE OR TASK. Client: CLIENT TYPE. Show a professional and a client having a calm, focused discussion. Only relevant work materials are on the table, with no legible confidential information. Natural gestures, soft daylight, and a professional setting. No staged handshake or added text."),
                                ],
                                [
                                    "label" => _w("A team at work"),
                                    "prompt_text" => _w("Create a realistic photograph of a small team working on PROJECT OR AREA OF WORK. Show a collaborative discussion of a specific task, distinct roles, and natural interaction. Work environment: DESCRIPTION. Appropriate tools and materials, animated facial expressions, and soft light. If employee photographs are attached, preserve their recognizable features. No added text."),
                                ],
                                [
                                    "label" => _w("Presenting a solution"),
                                    "prompt_text" => _w("Create a realistic business photograph of a solution being presented for CLIENT TASK. A professional explains the idea to a small group of clients using MOCKUP, PROTOTYPE, OR OTHER VISUAL AID. Show an engaged discussion and a clear focus of attention. A clean composition and natural lighting. No invented metrics, illegible charts, or added text."),
                                ],
                                [
                                    "label" => _w("Visiting a client"),
                                    "prompt_text" => _w("Create a realistic photograph of SERVICE being provided at a client's premises: TYPE OF BUSINESS OR FACILITY. Show a professional examining the surroundings, discussing the task with the client's representative, or carrying out a typical inspection. Workwear and tools suit the situation. Natural lighting, a clear focal point for the action, and a documentary style. No added text."),
                                ],
                                [
                                    "label" => _w("A service benefit"),
                                    "prompt_text" => _w("Create one illustration for a section highlighting the benefits of SERVICE. It needs to explain BENEFIT. Convey the meaning through a specific work situation or a clear arrangement of objects related to this service. Simple 3D graphics, soft shadows, a palette of COLORS, and a light background. Keep the main focus clear and the illustration easy to read in a small card. No invented numbers, awards, certificates, or text."),
                                ],
                            ]
                        ],
                        [
                            "heading" => _w("Delivery and logistics"),
                            "key" => "delivery_and_logistics",
                            "options" => [
                                [
                                    "label" => _w("Warehouse order fulfillment"),
                                    "prompt_text" => _w("Create a realistic photograph of an order being picked and packed with products from PRODUCT CATEGORY. Show an employee picking or packing an order in a neatly organized warehouse. Suitable containers, realistically sized boxes, and a clear sequence of actions. Neutral lighting, a clean work area, and a natural pose. No legible personal information or added text."),
                                ],
                                [
                                    "label" => _w("Handing over an order"),
                                    "prompt_text" => _w("Create a realistic advertising photograph of the delivery of PRODUCT OR ORDER TYPE. Delivery location: LOCATION. Show the moment a courier hands the order to a customer: the packaging is clearly visible, hand movements are natural, and emotions are restrained and friendly. Soft daylight, a clear composition, and realistic scale. No added text or incidental logos on clothing."),
                                ],
                                [
                                    "label" => _w("Freight transport"),
                                    "prompt_text" => _w("Create a realistic advertising photograph of TYPE OF CARGO being transported by TYPE OF TRANSPORT. Show the vehicle on an appropriate route or at a terminal, emphasizing its scale and connection to the logistics task. Realistic infrastructure, expressive natural light, and a clean composition. Leave an uncluttered area for a headline. Do not render the text itself or add new logos."),
                                ],
                                [
                                    "label" => _w("Loading and unloading"),
                                    "prompt_text" => _w("Create a realistic logistics photograph of TYPE OF CARGO being loaded into TYPE OF TRANSPORT. Show a characteristic work moment involving staff and appropriate equipment. Realistically sized cargo, an organized site, and workwear appropriate to the process. Natural light, a clear composition, and an action that is easy to read. No added text or incidental logos."),
                                ],
                                [
                                    "label" => _w("Packing fragile cargo"),
                                    "prompt_text" => _w("Create a realistic photograph of FRAGILE ITEM being prepared for transport. Packing materials: LIST OF MATERIALS. Show an employee's hands carefully securing the item inside its container. The protective layers and the arrangement of the contents are clearly visible. Soft light, a clean work surface, and crisp textures. Preserve the appearance of the item shown in the reference image. No added text."),
                                ],
                                [
                                    "label" => _w("How delivery works"),
                                    "prompt_text" => _w("Create one clear illustration of the TYPE OF DELIVERY process. Stages: SEQUENCE OF 3–4 STAGES. Show the same shipment moving through the specified stages, using recognizable scenes with packaging, employees, and vehicles. Simple isometric graphics, consistent scale, a light background, and neat arrows. Leave space for captions. Do not add unspecified stages, time frames, geographic routes, or text."),
                                ],
                            ]
                        ],
                    ],
                ],
                [
                    'key' => 'place',
                    'label' => _w('Place'),
                    'options' => [
                        [
                            "label" => _w("Logo"),
                            "prompt_text" => _w("Create a logo for BRAND NAME in INDUSTRY. Type: SYMBOL ONLY / SYMBOL WITH BRAND NAME / BRAND NAME ONLY. Use simple, distinctive shapes and minimal fine detail so the logo remains recognizable at a small size in a website header. If the selected type includes the brand name, reproduce it exactly without additional text. Show the logo itself on a clean, solid background, without placing it on products or signs."),
                        ],
                        [
                            "label" => _w("Hero Section"),
                            "prompt_text" => _w("Create an image for the hero section of a website in INDUSTRY. Main subject or scene: WHAT OR WHO TO SHOW. Build a striking composition with one clear focal point. Leave plenty of uncluttered space for a headline, description, and button. Position this space: LEFT / RIGHT / CENTER. Keep important details away from the edges to allow cropping. Create only the image, without any website interface elements. Do not add text unless it is specified separately."),
                        ],
                        [
                            "label" => _w("Website Background"),
                            "prompt_text" => _w("Create a realistic background image for a full-width website section in INDUSTRY. Choose a scene associated with the business: LANDSCAPE / INTERIOR / CLOSE-UP OF A MATERIAL. A group of objects, a surface, or a texture should fill the entire frame and form a cohesive, natural pattern. Overall tone: LIGHT / DARK. Use even lighting and soft transitions, without harsh contrasts or large, distracting objects. Text overlaid on the background should remain easy to read. No text or borders."),
                        ],
                        [
                            "label" => _w("Promotional Banner"),
                            "prompt_text" => _w("Create a promotional banner image for an offer: PROMOTION THEME OR OCCASION. Main visual focus: PRODUCT, SERVICE, OR MAIN SUBJECT. Convey the mood of the promotion through the setting and appropriate decorative details, without clutter. Leave clear space for a headline, offer details, and button. Position this space: LEFT / RIGHT / TOP / BOTTOM. Do not add text, prices, or discount percentages unless they are specified separately."),
                        ],
                        [
                            "label" => _w("Category Banner"),
                            "prompt_text" => _w("Create a banner image for CATEGORY on a website in INDUSTRY. Show products typical of this category or a clear visual representation of the service so visitors can immediately understand what the section offers. Keep the composition simple, with a small number of objects and an uncluttered background. Leave clear space for the category name. Position this space: LEFT / RIGHT / CENTER. Do not add text unless it is specified separately."),
                        ],
                        [
                            "label" => _w("Article Cover"),
                            "prompt_text" => _w("Create a cover image for an article about ARTICLE TOPIC. Format: PHOTOGRAPH / ILLUSTRATION / COLLAGE. Convey the main idea through one clear image or an easy-to-understand visual metaphor. The image should attract attention and remain understandable as a small article thumbnail. Avoid excessive fine detail and keep important subjects away from the edges to allow cropping. Do not add text unless it is specified separately."),
                        ],
                        [
                            "label" => _w("Section Illustration"),
                            "prompt_text" => _w("Create an eye-catching illustration for a NEWSLETTER / DELIVERY / PAYMENTS / FAQ / SUPPORT / CONSULTATION BOOKING section on a website in INDUSTRY. Format: FLAT ILLUSTRATION / 3D ILLUSTRATION / PHOTO COLLAGE. Build a compact composition using a few related elements that clearly communicate the section’s purpose. Place it on a simple, solid background with breathing room around it so it can sit alongside text. Do not add text unless it is specified separately."),
                        ],
                        [
                            "label" => _w("Simple Icon"),
                            "prompt_text" => _w("Create a simple, single-color icon representing CONCEPT OR OBJECT for a website button, link, or benefits section. Type: OUTLINE / SOLID. Color: COLOR. Use one easily recognizable symbol, a clear silhouette, and minimal detail so the icon remains legible at a small size. For the outline version, maintain a consistent stroke width. Center the icon on a transparent background with small, even margins. No gradients, shadows, or decorative backdrop."),
                        ],
                        [
                            "label" => _w("Portrait Photo"),
                            "prompt_text" => _w("Create a photorealistic portrait for a TESTIMONIALS / ABOUT US / TEAM section on a website in INDUSTRY. Person: GENDER, APPROXIMATE AGE, AND ROLE. Use a natural appearance, a friendly expression, clothing appropriate to the role, soft lighting, and an uncluttered background. Frame the portrait from the shoulders up, with the face clearly visible and enough space around the head for square or circular cropping. Preserve natural skin texture and avoid excessive retouching. If a reference photo of the person is attached, preserve their likeness."),
                        ],
                    ],
                ],
            ],
             "secondary" => [
                [
                    'key' => 'style',
                    'label' => _w('Style'),
                    'options' => [
                        [
                            "label" => _w("Product photography"),
                            "prompt_text" => _w("Create an image in the style of professional product photography. Clearly show the main subject's shape, material, texture, and small details, using carefully controlled highlights and realistic shadows. Preserve the background and camera angle specified in the prompt."),
                        ],
                        [
                            "label" => _w("Advertising photography"),
                            "prompt_text" => _w("Give the scene the look of an advertising shoot: a strong focal point, carefully arranged subjects, a clean finish, and clearly visible details. Use lighting and contrast to draw attention to the main subject while keeping materials and proportions natural."),
                        ],
                        [
                            "label" => _w("Lifestyle photography"),
                            "prompt_text" => _w("Capture the scene as a natural moment from everyday life. Use a believable setting, relaxed actions, authentic details, and lighting appropriate to the location. If people are present, avoid staged advertising poses and identical smiles."),
                        ],
                        [
                            "label" => _w("Photorealism"),
                            "prompt_text" => _w("Make the image look like a real photograph, with natural proportions, realistic surfaces, and consistent shadows and reflections. Preserve subtle details in materials and skin; avoid excessive smoothing and a plastic appearance."),
                        ],
                        [
                            "label" => _w("Minimalism"),
                            "prompt_text" => _w("Simplify the composition to the main subject and a few essential details. Leave plenty of open space and remove incidental decorations and visual clutter. Preserve the selected colors, scene, and rendering style."),
                        ],
                        [
                            "label" => _w("Editorial illustration"),
                            "prompt_text" => _w("Convey the theme through an expressive illustration for an article or cover, using a clear visual metaphor, distinct silhouettes, and a strong conceptual focus. Simplify secondary details so the idea is immediately clear. Do not add magazine layouts or text unless requested."),
                        ],
                        [
                            "label" => _w("Infographic"),
                            "prompt_text" => _w("Organize the content as a clear infographic, with logical groups, simple icons, an intuitive reading order, and connections between elements. Use only the facts, numbers, and labels specified in the prompt. If exact labels are not provided, explain the idea visually without inventing data."),
                        ],
                        [
                            "label" => _w("3D illustration"),
                            "prompt_text" => _w("Render the scene as a 3D illustration with clearly defined shapes, consistent materials, and soft contact shadows. Show depth and volume while preserving the subjects' recognizable features. Match the level of detail to the theme described."),
                        ],
                        [
                            "label" => _w("Isometric illustration"),
                            "prompt_text" => _w("Show the scene in isometric perspective, with consistent axis angles, parallel lines that do not converge toward the horizon, and a consistent scale across elements. Use simple three-dimensional shapes and space the subjects so their structure and relationships are easy to understand."),
                        ],
                        [
                            "label" => _w("Collage"),
                            "prompt_text" => _w("Build the composition as a collage of cutout images and graphic fragments. Use visible overlaps, variations in scale, and subtle shadows between layers. Maintain a single conceptual focus and a coordinated palette without filling the scene with unrelated elements."),
                        ],
                        [
                            "label" => _w("Watercolor"),
                            "prompt_text" => _w("Render the image in watercolor, with translucent washes, soft pigment bleeds, a subtle paper texture, and some areas left unpainted. Keep the main subjects' outlines recognizable and color transitions natural; avoid uniformly hard edges throughout the scene."),
                        ],
                        [
                            "label" => _w("Futurism"),
                            "prompt_text" => _w("Give the scene a futuristic feel, with streamlined, unusual shapes, technological details, and contemporary material combinations. Make the design reflect the subjects' intended functions. Do not add arbitrary interfaces, text, or decorative mechanisms that make the scene harder to understand."),
                        ],
                        [
                            "label" => _w("Hand-drawn illustration"),
                            "prompt_text" => _w("Create a light, hand-drawn illustration with lively, slightly uneven lines, simple recognizable shapes, and a few color accents. Keep the feel of quick sketches drawn by hand, with plenty of space between elements."),
                        ],
                        [
                            "label" => _w("Anime"),
                            "prompt_text" => _w("Use an anime style, with clean, expressive outlines, simplified shading with crisp edges, a carefully chosen palette, and expressive details. If people are present, stylize their appearance consistently. Preserve the specified age, action, and mood."),
                        ],
                    ],
                ],
                [
                    'key' => 'text',
                    'label' => _w('Text'),
                    'options' => [
                        [
                            "label" => _w("No text"),
                            "prompt_text" => _w("Do not add new text, letters, numbers, or decorative typographic elements to the image. If the scene includes a real product from the reference image, preserve its existing markings. Open space should follow the other composition requirements."),
                        ],
                        [
                            "label" => _w("Space for text — left"),
                            "prompt_text" => _w("Leave approximately the left third of the frame clear for a headline and a short description. This area should have a calm background without important subjects, fine details, or abrupt changes in brightness; shift the main scene to the right. Do not add the text itself."),
                        ],
                        [
                            "label" => _w("Space for text — right"),
                            "prompt_text" => _w("Leave approximately the right third of the frame clear for a headline and a short description. This area should have a calm background without important subjects, fine details, or abrupt changes in brightness; shift the main scene to the left. Do not add the text itself."),
                        ],
                        [
                            "label" => _w("Space for text — center"),
                            "prompt_text" => _w("Leave enough uncluttered space in the center of the frame for a headline. Place the main subjects closer to the edges without encroaching on the central area. The background in the center should have a consistent level of detail and brightness; do not add the text itself."),
                        ],
                        [
                            "label" => _w("Space for text — top"),
                            "prompt_text" => _w("Leave approximately the top third of the frame clear for a headline. Position the main subjects lower in the frame; keep the upper area calm, without fine details or abrupt changes in brightness. Do not add the text itself."),
                        ],
                        [
                            "label" => _w("Space for text — bottom"),
                            "prompt_text" => _w("Leave approximately the bottom third of the frame clear for a headline or a short message. Position the main subjects higher in the frame; keep the lower area calm, without fine details or abrupt changes in brightness. Do not add the text itself."),
                        ],
                        [
                            "label" => _w("Text — right"),
                            "prompt_text" => _w("Place the exact text \"TEXT\" on the right side of the image. Preserve its language, capitalization, and spelling, using large, legible letters with sufficient contrast against the background. Position the main subject further left, leave margins around the edges, and do not add any other text."),
                        ],
                        [
                            "label" => _w("Text — left"),
                            "prompt_text" => _w("Place the exact text \"TEXT\" on the left side of the image. Preserve its language, capitalization, and spelling, using large, legible letters with sufficient contrast against the background. Position the main subject further right, leave margins around the edges, and do not add any other text."),
                        ],
                        [
                            "label" => _w("Text — center"),
                            "prompt_text" => _w("Place the exact text \"TEXT\" in the center of the image. Preserve its language, capitalization, and spelling, using large, legible letters with sufficient contrast against the background. Arrange the main subjects around the text, leave ample margins, and do not add any other text."),
                        ],
                        [
                            "label" => _w("Text — top"),
                            "prompt_text" => _w("Place the exact text \"TEXT\" at the top of the image. Preserve its language, capitalization, and spelling, using large, legible letters with sufficient contrast against the background. Position the main scene below it, leave margins around the edges, and do not add any other text."),
                        ],
                        [
                            "label" => _w("Text — bottom"),
                            "prompt_text" => _w("Place the exact text \"TEXT\" at the bottom of the image. Preserve its language, capitalization, and spelling, using large, legible letters with sufficient contrast against the background. Position the main scene above it, leave margins around the edges, and do not add any other text."),
                        ],
                    ],
                ],
                [
                    'key' => 'people',
                    'label' => _w('People'),
                    'options' => [
                        [
                            "label" => _w("No people"),
                            "prompt_text" => _w("Show the scene without people, hands, or other body parts. Do not add human silhouettes in the background or in reflections. Preserve the objects and setting needed for the main scene."),
                        ],
                        [
                            "label" => _w("One person"),
                            "prompt_text" => _w("Include exactly one person who matches the description in the prompt. Do not add other people in the background. Their pose and action should fit the situation naturally."),
                        ],
                        [
                            "label" => _w("Several people"),
                            "prompt_text" => _w("Show a small group of NUMBER people. Their roles, clothing, and actions should match the described scene; give them distinct faces, poses, and expressions. Arrange them so their interactions are clear and the main focus is preserved."),
                        ],
                        [
                            "label" => _w("Hands only"),
                            "prompt_text" => _w("Show only hands and, if needed, forearms of any people in the frame. Depict a natural hand action from the main scene; keep faces and all other body parts outside the frame. Maintain realistic anatomy, finger positions, and scale relative to the objects."),
                        ],
                        [
                            "label" => _w("Looking at the camera"),
                            "prompt_text" => _w("The person described in the prompt looks directly at the camera. Align their gaze with their head position, and make their facial expression appropriate to the situation. Do not change the specified action or the person's character."),
                        ],
                        [
                            "label" => _w("Looking at the object"),
                            "prompt_text" => _w("The person described in the prompt looks at the main object or at the result of their own action. Their gaze and head angle should clearly show where their attention is focused."),
                        ],
                        [
                            "label" => _w("Looking away"),
                            "prompt_text" => _w("The person described in the prompt looks away from the camera with their head turned slightly. Keep their pose natural and their emotion appropriate to the scene; avoid an arbitrary, unnatural head position."),
                        ],
                        [
                            "label" => _w("Looking toward the text"),
                            "prompt_text" => _w("Direct the person's gaze toward the text area specified in the prompt. Their head should turn in the same direction, connecting the person to the composition. Do not add text unless it is separately requested."),
                        ],
                    ],
                ],
                [
                    'key' => 'background',
                    'label' => _w('Background'),
                    'options' => [
                        [
                            "label" => _w("Transparent"),
                            "prompt_text" => _w("Isolate the main subject on a truly transparent background. Preserve clean edges, fine details, and any translucent parts of the subject itself. Do not depict a checkerboard, a white rectangle, a surrounding scene, or a surface beneath the subject."),
                        ],
                        [
                            "label" => _w("Blurred"),
                            "prompt_text" => _w("Keep the main subject sharp while letting the background fall smoothly out of focus. Preserve the recognizable overall colors and shapes of the surroundings without distracting fine details. Keep the subject's edges natural, without halos or unintended blur on any part of it."),
                        ],
                        [
                            "label" => _w("Indoors"),
                            "prompt_text" => _w("Place the main scene in an interior: INTERIOR DESCRIPTION. Choose furniture and finishes appropriate to the room's purpose, with consistent scale, perspective, and lighting. The surroundings should support the scene without obscuring the main subject."),
                        ],
                        [
                            "label" => _w("In nature"),
                            "prompt_text" => _w("Use a natural setting: LANDSCAPE OR NATURAL LOCATION. Match the vegetation, terrain, and lighting to the selected location and season. Integrate the main subject naturally into the scene while keeping it clearly distinguishable from the background."),
                        ],
                        [
                            "label" => _w("Outdoors"),
                            "prompt_text" => _w("Place the scene outdoors: STREET, SQUARE, OR OTHER LOCATION. Include appropriate details of the setting and natural outdoor lighting. Keep perspective and scale consistent, with the main subject as the visual focus of the scene."),
                        ],
                        [
                            "label" => _w("Neutral"),
                            "prompt_text" => _w("Use a calm, neutral background without a noticeable pattern, objects, or details that suggest a specific setting or theme. Adjust the background's lightness to clearly separate the main subject from it. Preserve the selected color palette and natural contact shadows if the subject is resting on a surface."),
                        ],
                        [
                            "label" => _w("Abstract"),
                            "prompt_text" => _w("Create an abstract background using soft color transitions and a few simple shapes. Support the palette and mood of the main prompt while maintaining a clear visual focus. If a main subject is described, the background should not compete with it in detail or contrast."),
                        ],
                        [
                            "label" => _w("On a pedestal"),
                            "prompt_text" => _w("Place the main subject on a simple pedestal with realistic scale and a contact shadow. The pedestal's shape and material should complement the subject, while the surroundings remain uncluttered. Preserve the specified camera angle and keep important details of the subject unobstructed."),
                        ],
                        [
                            "label" => _w("White"),
                            "prompt_text" => _w("Use a pure white background without patterns or extraneous objects. Keep the main subject clearly separated from it, preserving texture in light details. If the subject is resting on a surface, add a soft, natural contact shadow."),
                        ],
                        [
                            "label" => _w("Solid color"),
                            "prompt_text" => _w("Use an even, solid-color background in COLOR OR HEX, without patterns, objects, or decorative elements. Maintain contrast between the main subject and the background; natural contact shadows are acceptable if the scene calls for them."),
                        ],
                        [
                            "label" => _w("Gradient"),
                            "prompt_text" => _w("Use a smooth background gradient from COLOR 1 to COLOR 2. Keep the transition soft and continuous, without visible banding or extraneous details. The main subject should stand out clearly against the selected shades."),
                        ],
                    ],
                ],
                [
                    'key' => 'camera',
                    'label' => _w('Camera'),
                    'options' => [
                        [
                            "label" => _w("Close-up"),
                            "prompt_text" => _w("Move in close to the main subject so it fills most of the image. Show important details and texture, with minimal secondary surroundings. Choose the crop so the main subject remains recognizable."),
                        ],
                        [
                            "label" => _w("Front view"),
                            "prompt_text" => _w("Show the main subject directly from the front, with the camera level with its center. For a portrait, place the camera at the person's eye level. Keep the horizon level and proportions natural, with no noticeable camera tilt."),
                        ],
                        [
                            "label" => _w("Side view"),
                            "prompt_text" => _w("Show the main subject from the side, at approximately a right angle to its front. Clearly reveal its side silhouette, depth, and distinctive details. For a person, use a profile view with a natural head position."),
                        ],
                        [
                            "label" => _w("Top-down view"),
                            "prompt_text" => _w("Position the camera directly above the scene, pointing vertically downward. Show how the objects are arranged on the surface, spacing important elements so they do not overlap. Avoid unintended camera tilt or a slanted horizon."),
                        ],
                        [
                            "label" => _w("High-angle view"),
                            "prompt_text" => _w("Show the scene from above at an angle of about 45 degrees. Make the objects' top surfaces, sides, volume, and relative positions clearly visible. Maintain a natural perspective without excessive wide-angle distortion."),
                        ],
                        [
                            "label" => _w("Low-angle view"),
                            "prompt_text" => _w("Position the camera below the main subject or the person's eye level and point it slightly upward. Emphasize height, scale, or an expressive silhouette while preserving recognizable proportions. Avoid excessive distortion of details closest to the camera."),
                        ],
                        [
                            "label" => _w("Wide shot"),
                            "prompt_text" => _w("Move the camera back to show the entire main subject along with enough of its surroundings. Show where the action takes place and how the subject relates to the space. Maintain a clear main focus."),
                        ],
                        [
                            "label" => _w("Three-quarter view"),
                            "prompt_text" => _w("Show the main subject at an angle of approximately 45 degrees from the front, so that both its front and side are visible. Position the camera roughly level with the center of the subject, using perspective to emphasize depth without strong distortion."),
                        ],
                    ],
                ],
                [
                    'key' => 'effects',
                    'label' => _w('Effects'),
                    'options' => [
                        [
                            "label" => _w("Lens flare"),
                            "prompt_text" => _w("Add subtle lens flare from a bright light source within the frame or near its edge. Match the direction and color of the flare to the scene's lighting. Keep it clear of faces, the main subject, and important details."),
                        ],
                        [
                            "label" => _w("Sunbeams"),
                            "prompt_text" => _w("Add visible beams of sunlight passing through an opening or part of the environment that suits the scene. Align the beams with the shadows and use a light atmospheric haze to make the light visible in the space. Preserve detail in the main subject without overexposure."),
                        ],
                        [
                            "label" => _w("Bokeh"),
                            "prompt_text" => _w("Keep the main subject sharp and render distant light sources as soft, blurred spots of light. Place the bokeh in the background to emphasize the scene's depth. Do not overlay large circles of light on the main subject."),
                        ],
                        [
                            "label" => _w("Spotlights"),
                            "prompt_text" => _w("Highlight the main subject with directional light from one or more spotlights. Create a distinct separation between lit areas and shadows, and align all pools of light with the positions of their sources. Include the lighting equipment itself only if the scene calls for it."),
                        ],
                        [
                            "label" => _w("Neon glow"),
                            "prompt_text" => _w("Add localized neon glow in the selected color palette. Let the colored light reflect softly on nearby surfaces and accentuate edges. Keep the main subject clearly visible and do not add neon lettering unless specifically requested."),
                        ],
                        [
                            "label" => _w("Direct flash"),
                            "prompt_text" => _w("Recreate the look of a photograph taken with direct flash close to the camera: a crisp foreground, noticeable highlights, and harder shadows behind objects. Preserve texture in light surfaces and skin. Do not depict a separate burst of light as an object in the frame."),
                        ],
                        [
                            "label" => _w("Smoke"),
                            "prompt_text" => _w("Add localized wisps of translucent smoke with a clear direction of movement. Arrange them around the main scene while keeping silhouettes and important details clearly visible. Do not add fire or a source of smoke unless the scene calls for it."),
                        ],
                        [
                            "label" => _w("Fog"),
                            "prompt_text" => _w("Add light, diffuse fog to enhance the sense of depth: distant areas have lower contrast, while closer areas remain clearer. Keep the main subject recognizable and preserve a natural transition from foreground to background."),
                        ],
                        [
                            "label" => _w("Sparks"),
                            "prompt_text" => _w("Add a small number of bright sparks around the main action. Match their paths and direction to the movement in the scene, and keep their brightness from obscuring important details. Do not depict damage to the subject, a fire, or an explosion unless specifically requested."),
                        ],
                        [
                            "label" => _w("Airborne particles"),
                            "prompt_text" => _w("Add a few fine suspended particles visible in illuminated areas of the air. Vary their distance from the camera to create depth, with some particles slightly blurred. Keep the main subject and surfaces clean and clearly visible."),
                        ],
                        [
                            "label" => _w("Confetti"),
                            "prompt_text" => _w("Add a moderate amount of falling confetti in colors that complement the overall palette. Place it at different depths to convey movement and a festive mood. Keep it clear of faces, the main subject, and any designated text area."),
                        ],
                        [
                            "label" => _w("Rain"),
                            "prompt_text" => _w("Create a rainy atmosphere with visible raindrops in the air, wet exposed surfaces, and soft light reflections. Keep the direction of the rain consistent throughout the scene. For indoor scenes, show rain through a window or outside, keeping the interior dry."),
                        ],
                        [
                            "label" => _w("Snow"),
                            "prompt_text" => _w("Add gently falling snow, varying the size and sharpness of the snowflakes according to their distance from the camera. A light layer of snow may cover exposed outdoor surfaces. In indoor scenes, snow should be visible only outside; keep the main subject clearly visible."),
                        ],
                        [
                            "label" => _w("Surface reflection"),
                            "prompt_text" => _w("Add a subtle reflection of the main subject on a glossy surface beneath it. Align the reflection with the subject's position, camera angle, and lighting, and make it slightly less pronounced than the subject itself. Avoid extra duplicates and reflections unrelated to the scene."),
                        ],
                        [
                            "label" => _w("Motion blur"),
                            "prompt_text" => _w("Convey movement through directional blur in the background and secondary moving details. Keep the main subject recognizable and mostly sharp. Match the direction of the blur to the action without distorting object shapes or human anatomy."),
                        ],
                        [
                            "label" => _w("Backlight"),
                            "prompt_text" => _w("For a striking lighting effect, place the main light source behind the main subject or behind it and to one side. A thin rim of light should separate the subject from the background, while the front remains sufficiently lit to reveal important details."),
                        ],
                    ],
                ],
                [
                    'key' => 'color',
                    'label' => _w('Color'),
                    'prompt_prefix' => _w('Color palette'),
                    'custom_color_label' => _w('Custom color'),
                    'swatches' => [
                        ['color' => '#e94646', 'label' => _w('Red')],
                        ['color' => '#e99d46', 'label' => _w('Orange')],
                        ['color' => '#e9d946', 'label' => _w('Yellow')],
                        ['color' => '#88dd39', 'label' => _w('Lime')],
                        ['color' => '#3cd951', 'label' => _w('Green')],
                        ['color' => '#0ce8c0', 'label' => _w('Turquoise')],
                        ['color' => '#5dddfa', 'label' => _w('Light blue')],
                        ['color' => '#5a75ef', 'label' => _w('Blue')],
                        ['color' => '#823af5', 'label' => _w('Violet')],
                        ['color' => '#e946c3', 'label' => _w('Magenta')],
                        ['color' => '#e9467f', 'label' => _w('Pink')],
                    ],
                    'options' => [
                        [
                            "label" => _w("Preset and custom colors"),
                            "prompt_text" => _w("Use COLOR NAME (HEX) as the main color for the surroundings and decorative accents. Choose coordinating shades and keep the main subject clearly distinguishable. Do not recolor the product from the reference image or alter natural skin tones unless specifically requested."),
                        ],
                        [
                            "label" => _w("Shades of one color"),
                            "prompt_text" => _w("Build the surrounding palette from shades of a single selected color, varying their lightness and saturation. If no color is specified, use the main color from the scene description. Keep shapes clearly defined and preserve natural skin tones and the original colors of the product from the reference image."),
                        ],
                        [
                            "label" => _w("Warm tones"),
                            "prompt_text" => _w("Use mainly warm tones in the surroundings and styling, such as cream, sand, terracotta, or other shades suited to the scene. Preserve natural skin tones and the colors of the product from the reference image. Do not apply a uniform orange cast to the entire image."),
                        ],
                        [
                            "label" => _w("Cool tones"),
                            "prompt_text" => _w("Use mainly cool tones in the surroundings and styling, such as light blue, blue-gray, turquoise, or other shades suited to the scene. Preserve natural skin tones and the colors of the product from the reference image. Do not give white surfaces and skin a uniform blue cast."),
                        ],
                        [
                            "label" => _w("Vibrant colors"),
                            "prompt_text" => _w("Use a rich, expressive palette with a few coordinated color accents. Preserve fine detail, natural-looking skin, and the original colors of the product from the reference image. Avoid excessive saturation across the entire image."),
                        ],
                        [
                            "label" => _w("Pastel tones"),
                            "prompt_text" => _w("Use soft, light shades with low saturation in the surroundings and decorative elements. Maintain enough contrast between the main subject and the background. Do not wash out the product from the reference image or make the image look faded."),
                        ],
                        [
                            "label" => _w("Gold accents"),
                            "prompt_text" => _w("Add subtle gold accents to the surroundings and styling. Use realistic golden highlights on metal details and suitable warm tones on other surfaces. Preserve the main subject's material and original color."),
                        ],
                        [
                            "label" => _w("Light palette"),
                            "prompt_text" => _w("Keep the surroundings predominantly light, with soft transitions between shades and an airy feel. Preserve depth, texture, and clear separation between light-colored objects and the background. Avoid overexposure and loss of important details."),
                        ],
                        [
                            "label" => _w("Dark palette"),
                            "prompt_text" => _w("Use mainly deep, dark shades in the surroundings and styling. Separate the main subject from the background through differences in tone or color, or a subtle lighting accent. Preserve shadow detail and the original colors of the product from the reference image."),
                        ],
                        [
                            "label" => _w("Black and white"),
                            "prompt_text" => _w("Render the entire image in grayscale, with no colored areas or sepia tones. Distinguish objects through lightness, texture, and contrast. Preserve detail in both light and dark areas."),
                        ],
                    ],
                ],
            ]
        ];
    }
}
