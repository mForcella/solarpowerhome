
# calculate the yearly energy yield for a given hardware configuration at a handful of sites listed below

import pvlib

import pandas as pd

# import matplotlib.pyplot as plt

from flask import Flask
from flask import request

app = Flask(__name__)

@app.route('/')

def flasktest():

    module = request.args.get('module')

    # get module list
    sandia_modules = pvlib.pvsystem.retrieve_sam('SandiaMod')
    cec_modules = pvlib.pvsystem.retrieve_sam('CECMod')
    # print("\nSandia Modules")
    # print(sandia_modules)
    # print("\nCEC Modules")
    # print(cec_modules)

    # get inverter list
    sapm_inverters = pvlib.pvsystem.retrieve_sam('cecinverter')
    adr_inverters = pvlib.pvsystem.retrieve_sam('ADRInverter')
    # print("\nCEC Intervers")
    # print(sapm_inverters)
    # print("\nADR Intervers")
    # print(adr_inverters)

    # ?
    temperature_model_parameters = pvlib.temperature.TEMPERATURE_MODEL_PARAMETERS['sapm']['open_rack_glass_glass']


    # In order to retrieve meteorological data for the simulation, we can make use of the IO Tools module.
    # In this example we will be using PVGIS to retrieve a Typical Meteorological Year (TMY) which includes irradiation, temperature and wind speed.

    # site coordinates
    coordinates = [
        (32.2, -111.0, 'Tucson', 700, 'Etc/GMT+7'),
        # (35.1, -106.6, 'Albuquerque', 1500, 'Etc/GMT+7'),
        # (37.8, -122.4, 'San Francisco', 10, 'Etc/GMT+8'),
        # (52.5, 13.4, 'Berlin', 34, 'Etc/GMT-1'),
    ]

    # Typical Meteorological Year - irradiation, temperature and wind speed
    tmys = []

    # get weather for each location
    for location in coordinates:
        latitude, longitude, name, altitude, timezone = location
        weather = pvlib.iotools.get_pvgis_tmy(latitude, longitude)[0]
        weather.index.name = "utc_time"
        tmys.append(weather)
    # print(tmys)


    # set selected module
    module = sandia_modules['Canadian_Solar_CS5P_220M___2009_']

    # set selected inverter
    inverter = sapm_inverters['ABB__MICRO_0_25_I_OUTD_US_208__208V_']

    # set the PV system
    system = {'module': module, 'inverter': inverter,
              'surface_azimuth': 180}


    # get the yearly energy output for each location using the configured PV system

    energies = {}

    for location, weather in zip(coordinates, tmys):
        latitude, longitude, name, altitude, timezone = location
        system['surface_tilt'] = latitude
        solpos = pvlib.solarposition.get_solarposition(
            time=weather.index,
            latitude=latitude,
            longitude=longitude,
            altitude=altitude,
            temperature=weather["temp_air"],
            pressure=pvlib.atmosphere.alt2pres(altitude),
        )
        dni_extra = pvlib.irradiance.get_extra_radiation(weather.index)
        airmass = pvlib.atmosphere.get_relative_airmass(solpos['apparent_zenith'])
        pressure = pvlib.atmosphere.alt2pres(altitude)
        am_abs = pvlib.atmosphere.get_absolute_airmass(airmass, pressure)
        aoi = pvlib.irradiance.aoi(
            system['surface_tilt'],
            system['surface_azimuth'],
            solpos["apparent_zenith"],
            solpos["azimuth"],
        )
        total_irradiance = pvlib.irradiance.get_total_irradiance(
            system['surface_tilt'],
            system['surface_azimuth'],
            solpos['apparent_zenith'],
            solpos['azimuth'],
            weather['dni'],
            weather['ghi'],
            weather['dhi'],
            dni_extra=dni_extra,
            model='haydavies',
        )
        cell_temperature = pvlib.temperature.sapm_cell(
            total_irradiance['poa_global'],
            weather["temp_air"],
            weather["wind_speed"],
            **temperature_model_parameters,
        )
        effective_irradiance = pvlib.pvsystem.sapm_effective_irradiance(
            total_irradiance['poa_direct'],
            total_irradiance['poa_diffuse'],
            am_abs,
            aoi,
            module,
        )
        dc = pvlib.pvsystem.sapm(effective_irradiance, cell_temperature, module)
        ac = pvlib.inverter.sandia(dc['v_mp'], dc['p_mp'], inverter)
        annual_energy = ac.sum()
        energies[name] = annual_energy

    # energies = pd.Series(energies)
    return energies
    # print(energies)
