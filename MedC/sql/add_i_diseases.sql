INSERT INTO disease_information (disease_name, overview, symptoms, causes, videos, infographics, organ_name)
SELECT
    'Influenza',
    'Influenza, commonly called the flu, is a contagious viral infection that affects the nose, throat, and lungs. It often appears suddenly and can spread quickly in schools, offices, and households during seasonal outbreaks. Although many people recover within a week or two, influenza can become serious in young children, older adults, pregnant women, and people with chronic medical conditions.',
    'Influenza usually begins suddenly with fever, chills, headache, tiredness, and body aches. Many people also develop a dry cough, sore throat, blocked or runny nose, and discomfort in the chest. Some may feel weak, lose appetite, or experience mild nausea.',
    'Influenza is caused by influenza viruses, mainly type A and type B. The infection spreads through droplets released when an infected person coughs, sneezes, or talks, and it can also spread from contaminated surfaces to the eyes, nose, or mouth. Crowded indoor spaces, close contact, and weak immunity increase the risk of infection.',
    '["https://www.youtube.com/embed/WstxaoeAB3g","https://www.youtube.com/embed/jYhnlqE1cJY?si=cZXTzGyWl6aniLbW","https://www.youtube.com/embed/lLaPKP96jXA?si=jHhWLvJe-u1i9n-U"]',
    '["overview.svg"]',
    'Lungs'
WHERE NOT EXISTS (
    SELECT 1 FROM disease_information WHERE disease_name = 'Influenza'
);

INSERT INTO disease_information (disease_name, overview, symptoms, causes, videos, infographics, organ_name)
SELECT
    'Indigestion',
    'Indigestion, also known as dyspepsia, describes discomfort or pain in the upper abdomen, often after eating. It is a common digestive complaint rather than a disease by itself, and it may happen occasionally or come back repeatedly depending on food habits, stress, or underlying stomach conditions.',
    'People with indigestion often feel fullness soon after starting a meal, heaviness after eating, bloating, burping, nausea, or a burning sensation in the upper abdomen. Some also report a sour taste in the mouth or mild stomach pain after spicy or oily foods.',
    'Indigestion can be triggered by overeating, eating too quickly, spicy or fatty food, caffeine, alcohol, smoking, or emotional stress. It may also be linked to acid reflux, gastritis, stomach ulcers, or the side effects of certain medicines such as painkillers.',
    '["https://www.youtube.com/embed/0BzmEntoMQU","https://www.youtube.com/embed/FXlzv9cVVo8?si=aJh3W6KbG3T4edB3","https://www.youtube.com/embed/LUssH0kxFdQ?si=XZKxkAoA8cekg8fA"]',
    '["overview.svg"]',
    'Stomach'
WHERE NOT EXISTS (
    SELECT 1 FROM disease_information WHERE disease_name = 'Indigestion'
);

INSERT INTO disease_information (disease_name, overview, symptoms, causes, videos, infographics, organ_name)
SELECT
    'Insomnia',
    'Insomnia is a sleep disorder in which a person has trouble falling asleep, staying asleep, or getting restful sleep. It may last for a short period during stress or continue for weeks or months when linked to mental health issues, lifestyle habits, or other medical conditions.',
    'Common signs include lying awake for a long time before sleep, waking up often during the night, rising too early, and feeling unrefreshed in the morning. Daytime tiredness, poor concentration, low mood, irritability, and reduced work performance are also common.',
    'Insomnia may be caused by stress, anxiety, depression, irregular sleep schedules, too much screen time before bed, excess caffeine, or poor sleep habits. Chronic pain, breathing disorders, medicines, and other health conditions can also disturb normal sleep.',
    '["https://www.youtube.com/embed/vdc8JonEax8"]',
    '["overview.svg"]',
    'Brain'
WHERE NOT EXISTS (
    SELECT 1 FROM disease_information WHERE disease_name = 'Insomnia'
);

INSERT INTO disease_information (disease_name, overview, symptoms, causes, videos, infographics, organ_name)
SELECT
    'Infectious Mononucleosis',
    'Infectious mononucleosis, often called mono, is a viral illness most commonly caused by the Epstein-Barr virus. It frequently affects teenagers and young adults and is known for causing intense tiredness, sore throat, and swollen glands. Recovery can take longer than many routine viral infections.',
    'Typical symptoms include severe sore throat, fever, swollen lymph nodes in the neck, tiredness, headache, and enlarged tonsils. Some people may also develop body aches, reduced appetite, or a skin rash, and fatigue may continue for several weeks.',
    'Mono is usually caused by the Epstein-Barr virus and spreads mainly through saliva, which is why it is sometimes called the kissing disease. It can also spread through shared utensils, drinks, or close contact with respiratory secretions from an infected person.',
    '["https://www.youtube.com/embed/MYfiei0n4KY"]',
    '["overview.svg"]',
    'Throat'
WHERE NOT EXISTS (
    SELECT 1 FROM disease_information WHERE disease_name = 'Infectious Mononucleosis'
);

INSERT INTO disease_information (disease_name, overview, symptoms, causes, videos, infographics, organ_name)
SELECT
    'Irritable Bowel Syndrome',
    'Irritable bowel syndrome, or IBS, is a long-term digestive condition that affects how the intestines function. It does not usually cause structural damage to the bowel, but it can significantly affect daily life by causing pain, bloating, and changes in bowel movements.',
    'IBS commonly causes abdominal pain or cramping, bloating, excess gas, and changes in bowel habits such as diarrhea, constipation, or both. Symptoms often get worse after meals or during stress, and many people feel relief after passing stool.',
    'The exact cause of IBS is not fully understood, but it is linked to abnormal gut movement, increased bowel sensitivity, stress, changes in the gut-brain connection, and sometimes infections or food intolerance. Triggers differ from person to person and may include certain foods, anxiety, or hormonal changes.',
    '["https://www.youtube.com/embed/Z-JEXuxuep8"]',
    '["overview.svg"]',
    'intestine'
WHERE NOT EXISTS (
    SELECT 1 FROM disease_information WHERE disease_name = 'Irritable Bowel Syndrome'
);
